#!/usr/bin/env python3
"""แปลงไฟล์ md/*.md เป็น latex/chapters/*.tex (เรียกจาก build.sh ใน Docker)
- ใช้ pandoc แปลงเนื้อหา แล้วปรับให้เข้ากับรูปแบบรายงานไทย
- หัวข้อในไฟล์ .md มีเลขบท/ข้อกำกับไว้แล้ว (เช่น "บทที่ 1", "1.1") จึงใช้หัวข้อแบบไม่ใส่เลขซ้ำ แล้วเพิ่มเข้าสารบัญเอง
"""
import pathlib, re, subprocess, sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
SRC, OUT = ROOT / "md", ROOT / "latex" / "chapters"
SKIP = {"00-cover.md"}          # ปกทำใน main.tex

def figure(m):
    """รูปกว้างตามขนาดจริง (ภาพหน้าจอเล็กไม่ถูกขยายจนแตก) สูงสุดเต็มความกว้างหน้า"""
    path = m.group(1)
    w = 0.95
    try:
        with open(ROOT / path, "rb") as f:
            head = f.read(24)
        if head[:8] == b"\x89PNG\r\n\x1a\n":
            px = int.from_bytes(head[16:20], "big")
            w = max(0.5, min(1.0, px / 1100))
    except OSError:
        pass
    return f"@@FIG{{{w:.2f}}}{{{path}}}@@"

def floats(tex):
    """รูป + บรรทัด "ภาพที่ ..." ที่ตามมา → figure ลอยตัว (LaTeX วางให้ไม่เหลือช่องว่างครึ่งหน้า) พร้อมคำบรรยายใต้ภาพ"""
    def fig(m):
        w, path, cap = m.group(1), m.group(2), (m.group(3) or "").strip()
        body = f"\\centering\\includegraphics[width={w}\\linewidth,height=0.42\\textheight,keepaspectratio]{{{path}}}"
        if cap:
            num, _, rest = cap.partition(" ")
            num2, _, rest2 = rest.partition(" ")
            body += f"\n\\caption*{{\\textbf{{{num} {num2}}} {rest2}}}"
        return f"\\begin{{figure}}[!htbp]\n{body}\n\\end{{figure}}"
    return re.sub(r"@@FIG\{([\d.]+)\}\{([^}]*)\}@@(?:\s*\n\s*\n(ภาพที่ [^\n]*(?:\n(?!\n)[^\n]*)*))?", fig, tex)

def widen(m):
    """ตารางกว้างตามเนื้อหา: แบ่งความกว้างคอลัมน์ตามความยาวข้อความ ให้ตัดบรรทัดได้ไม่ล้นขอบ"""
    n, body = len(m.group(1)), m.group(2)
    lens = [1] * n
    for row in re.findall(r"^(.*?)\\\\\s*$", body, flags=re.M):
        cells = row.split(" & ")
        if len(cells) != n: continue
        for i, c in enumerate(cells):
            lens[i] = max(lens[i], min(len(re.sub(r"\\[a-zA-Z]+|[{}]", "", c)), 60))
    lens = [l ** 0.7 for l in lens]                      # ลดความต่าง: คอลัมน์สั้นไม่แคบจนตัวอักษรเรียงลงทีละคำ
    tot = sum(lens)
    w = [max(0.16 if n > 2 else 0.25, l / tot) for l in lens]; k = sum(w)
    spec = "".join(f">{{\\raggedright\\arraybackslash}}p{{{0.97 * x / k:.3f}\\linewidth - 2\\tabcolsep}}" for x in w)
    return "\\begingroup\\small\\begin{longtable}[]{@{}" + spec + "@{}}" + body + "\\end{longtable}\\endgroup"

def convert(md: pathlib.Path) -> str:
    tex = subprocess.run(
        ["pandoc", str(md), "-f", "gfm", "-t", "latex", "--top-level-division=chapter",
         "--shift-heading-level-by=-1", "--no-highlight", "--columns=50", "--wrap=none"],
        check=True, capture_output=True, text=True).stdout
    tex = re.sub(r"\\hypertarget\{[^}]*\}\{%\n", "", tex)
    def head(m):
        cmd, title = m.group(1), m.group(2).replace("\n", " ").strip()
        if cmd == "chapter":                            # บทใหม่: "บทที่ N" บรรทัดบน ชื่อบทบรรทัดล่าง กึ่งกลาง
            mm = re.match(r"(บทที่ \d+) (.+)", title)
            top, name = (mm.group(1), mm.group(2)) if mm else ("", title)
            return f"\\thaichapter{{{top}}}{{{name}}}\\addcontentsline{{toc}}{{chapter}}{{{title}}}"
        toc = "section" if cmd == "section" else None
        return f"\\{cmd}*{{{title}}}" + (f"\\addcontentsline{{toc}}{{{toc}}}{{{title}}}" if toc else "")
    tex = re.sub(r"\\(chapter|section|subsection|subsubsection)\{(.*?)\}\\label\{[^}]*\}\}?", head, tex, flags=re.S)
    tex = re.sub(r"\\includegraphics\{([^}]*)\}", figure, tex)
    tex = floats(tex)
    tex = re.sub(r"(?m)^(ตารางที่ \d+\.\d+)", r"\\needspace{7\\baselineskip}\\noindent\\textbf{\1}", tex)
    tex = re.sub(r"\\begin\{longtable\}\[\]\{@\{\}(l+)@\{\}\}(.*?)\\end\{longtable\}", widen, tex, flags=re.S)
    tex = tex.replace("\\begin{verbatim}", "\\begin{Verbatim}[breaklines,breakanywhere,fontsize=\\small,frame=single,framesep=6pt]")
    tex = tex.replace("\\end{verbatim}", "\\end{Verbatim}")
    tex = re.sub(r"\n(คณะผู้จัดทำ)\n\n(\d+ \S+ \d{4})\s*$", r"\n\\begin{flushright}\1\\\\[4pt]\2\\end{flushright}\n", tex)   # ลงชื่อท้ายคำนำชิดขวา
    tex = tex.replace("≥", "$\\geq$").replace("≤", "$\\leq$")
    return tex

def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for md in sorted(SRC.glob("*.md")):
        if md.name in SKIP: continue
        (OUT / (md.stem + ".tex")).write_text(convert(md), encoding="utf-8")
        print("  ", md.name, "->", md.stem + ".tex")

if __name__ == "__main__":
    sys.exit(main())
