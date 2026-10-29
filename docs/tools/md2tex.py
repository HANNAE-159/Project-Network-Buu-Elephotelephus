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
            w = max(0.6, min(1.0, px / 1100))     # ภาพเล็ก (เช่น terminal) ไม่ต่ำกว่า 60% ให้อ่านออก
    except OSError:
        pass
    return f"@@FIG{{{w:.2f}}}{{{path}}}@@"

def floats(tex):
    """รูป + บรรทัด "ภาพที่ ..." ที่ตามมา → figure วางตรงตำแหน่งที่เขียนไว้ [H] (ไม่ลอยไปแทรกกลางหัวข้ออื่น) พร้อมคำบรรยายใต้ภาพ"""
    def fig(m):
        w, path, cap = m.group(1), m.group(2), (m.group(3) or "").strip()
        body = f"\\centering\\includegraphics[width={w}\\linewidth,height=0.48\\textheight,keepaspectratio]{{{path}}}"
        if cap:
            num, _, rest = cap.partition(" ")
            num2, _, rest2 = rest.partition(" ")
            short = ALTS.get(path, rest2)          # สารบัญภาพใช้ชื่อย่อจาก alt text ใน .md
            body += f"\n\\caption*{{\\textbf{{{num} {num2}}} {rest2}}}\\addcontentsline{{lof}}{{figure}}{{{num} {num2} {short}}}"
        return f"\\begin{{figure}}[H]\n{body}\n\\end{{figure}}"
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

def attach_captions(tex):
    """ใส่ชื่อ "ตารางที่ X.Y" ให้ติดกับตาราง และบันทึกสารบัญตารางจากตำแหน่งของตารางจริง
    - ตารางสั้น (ไม่เกิน SHORT_ROWS แถว): ทำเป็นกล่องเดียวที่ไม่แตกข้ามหน้า (ชื่อ + ตาราง) หัวข้อที่อยู่ก่อนหน้าจึงไปพร้อมตาราง
    - ตารางยาว: ใช้ longtable ชื่ออยู่ในหัวตารางหน้าแรก หน้าถัดไปมี "ตารางที่ X.Y (ต่อ)" """
    pat = re.compile(
        r"\\keepspace\{7\\baselineskip\}\\noindent\\textbf\{(ตารางที่ [\dก-ฮ]+\.\d+)\}([^\n]*)\n\n"
        r"\\begingroup\\small\\begin\{longtable\}\[\]\{(.*?)\}\n"
        r"\\toprule\\noalign\{\}\n(.*?\\\\)\n\\midrule\\noalign\{\}\n\\endhead\n"
        r"\\bottomrule\\noalign\{\}\n\\endlastfoot\n(.*?)\\end\{longtable\}\\endgroup", re.S)
    def rep(m):
        num, rest, spec, header, rows = m.group(1), m.group(2).strip(), m.group(3), m.group(4), m.group(5)
        lot = f"\\addcontentsline{{lot}}{{table}}{{{num} {rest}}}"
        title = f"\\textbf{{{num}}} {rest}"
        if len(re.findall(r"\\\\\s*$", rows, flags=re.M)) <= SHORT_ROWS:
            return ("\\par\\vspace{\\LTpre}\\noindent\\begin{minipage}{\\linewidth}\\phantomsection" + lot
                    + f"{{\\normalsize {title}\\par}}\\vspace{{4pt}}\\small\n\\begin{{tabular}}{{{spec}}}\n\\toprule\n{header}\n\\midrule\n"
                    + rows + "\\bottomrule\n\\end{tabular}\\end{minipage}\\par\\vspace{\\LTpost}")
        n = spec.count("p{")
        cap = lambda t: f"\\multicolumn{{{n}}}{{@{{}}l@{{}}}}{{\\normalsize {t}}}\\\\[2pt]\n"
        return ("\\begingroup\\small\\begin{longtable}[]{" + spec + "}\n"
                + cap("\\phantomsection" + lot + title) + f"\\toprule\\noalign{{}}\n{header}\n\\midrule\\noalign{{}}\n\\endfirsthead\n"
                + cap(f"\\textbf{{{num}}} (ต่อ)") + f"\\toprule\\noalign{{}}\n{header}\n\\midrule\\noalign{{}}\n\\endhead\n"
                + "\\bottomrule\\noalign{}\n\\endlastfoot\n" + rows + "\\end{longtable}\\endgroup")
    return pat.sub(rep, tex)

SHORT_ROWS = 12

def keep_together(tex):
    """จัดหัวข้อ/ข้อความไม่ให้ค้างท้ายหน้า"""
    # 1) ย่อหน้าสั้นที่นำหน้ารูป (เช่น "ผลการรัน ... ") ห้ามแยกหน้าจากรูป
    tex = re.sub(r"(?m)^([^\\\n][^\n]{0,160})\n\n(\\begin\{figure\}\[H\])", r"\1\\par\\nopagebreak[4]\n\n\2", tex)
    # 2) หัวข้อที่ตามด้วยเนื้อหาสั้น (รายการ ย่อหน้า ตารางสั้น) ให้ขึ้นหน้าใหม่พร้อมเนื้อหาถ้าที่เหลือไม่พอ
    parts = re.split(r"(?m)^(?=\\(?:sub)*section\*\{)", tex)
    out = [parts[0]]
    for blk in parts[1:]:
        body = blk.split("\n", 1)[1] if "\n" in blk else ""
        if "\\begin{longtable}" in body or "\\begin{figure}" in body:
            out.append(blk); continue                    # ตารางยาว/รูปจัดการตัวเอง (needspace ก่อน longtable ทำให้หัวตารางซ้ำ)
        text = re.sub(r"\\[a-zA-Z]+\*?|[{}\[\]]", "", body)
        lines = sum(len(x) // 75 + 1 for x in text.split("\n") if x.strip()) + body.count("\\item")
        need = max(4, min(lines + 2, 16))
        out.append(f"\\keepspace{{{need}\\baselineskip}}" + blk)
    return "".join(out)

ALTS = {}

def convert(md: pathlib.Path) -> str:
    for alt, src in re.findall(r"!\[([^\]]*)\]\(([^)]+)\)", md.read_text(encoding="utf-8")):
        ALTS[src] = alt
    tex = subprocess.run(
        ["pandoc", str(md), "-f", "gfm", "-t", "latex", "--top-level-division=chapter",
         "--shift-heading-level-by=-1", "--no-highlight", "--columns=50", "--wrap=none"],
        check=True, capture_output=True, text=True).stdout
    tex = re.sub(r"\\hypertarget\{[^}]*\}\{%\n", "", tex)
    def head(m):
        cmd, title = m.group(1), m.group(2).replace("\n", " ").strip()
        if cmd == "chapter" and title == "ภาคผนวก":     # หน้าคั่นภาคผนวก: คำว่า "ภาคผนวก" กลางหน้า
            return ("\\clearpage\\thispagestyle{plain}\\phantomsection\\addcontentsline{toc}{chapter}{ภาคผนวก}"
                    "\\vspace*{\\fill}{\\centering\\bfseries\\Huge ภาคผนวก\\par}\\vspace*{\\fill}")
        ma = re.match(r"(ภาคผนวก \S+) (.+)", title)
        if cmd == "section" and ma:                     # ภาคผนวกแต่ละข้อขึ้นหน้าใหม่ หัวกึ่งกลางแบบเดียวกับหัวบท
            return f"\\thaichapter{{{ma.group(1)}}}{{{ma.group(2)}}}\\addcontentsline{{toc}}{{section}}{{{title}}}"
        if cmd == "chapter":                            # บทใหม่: "บทที่ N" บรรทัดบน ชื่อบทบรรทัดล่าง กึ่งกลาง
            mm = re.match(r"(บทที่ \d+) (.+)", title)
            top, name = (mm.group(1), mm.group(2)) if mm else ("", title)
            return f"\\thaichapter{{{top}}}{{{name}}}\\addcontentsline{{toc}}{{chapter}}{{{title}}}"
        toc = "section" if cmd == "section" else None
        return f"\\{cmd}*{{{title}}}" + (f"\\addcontentsline{{toc}}{{{toc}}}{{{title}}}" if toc else "")
    tex = re.sub(r"\\(chapter|section|subsection|subsubsection)\{(.*?)\}\\label\{[^}]*\}\}?", head, tex, flags=re.S)
    tex = re.sub(r"\\includegraphics\{([^}]*)\}", figure, tex)
    tex = floats(tex)
    tex = re.sub(r"(?m)^(ตารางที่ [\dก-ฮ]+\.\d+)", r"\\keepspace{7\\baselineskip}\\noindent\\textbf{\1}", tex)
    tex = re.sub(r"\\begin\{longtable\}\[\]\{@\{\}(l+)@\{\}\}(.*?)\\end\{longtable\}", widen, tex, flags=re.S)
    tex = attach_captions(tex)
    tex = keep_together(tex)
    tex = tex.replace("\\begin{verbatim}", "\\begin{Verbatim}[breaklines,breakanywhere,fontsize=\\small,frame=single,framesep=6pt]")
    tex = tex.replace("\\end{verbatim}", "\\end{Verbatim}")
    tex = re.sub(r"\n(คณะผู้จัดทำ)\n\n(\d+ \S+ \d{4})\s*$", r"\n\\begin{flushright}\1\\\\[4pt]\2\\end{flushright}\n", tex)   # ลงชื่อท้ายคำนำชิดขวา
    url = (ROOT / "repo-url.txt").read_text(encoding="utf-8").strip() if (ROOT / "repo-url.txt").exists() else ""
    tex = tex.replace("REPOURL", f" ที่ \\url{{{url}}}" if url else "")      # ลิงก์ GitHub จาก docs/repo-url.txt
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
