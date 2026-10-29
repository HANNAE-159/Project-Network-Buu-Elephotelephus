#!/usr/bin/env python3
"""แปลงไฟล์ md/*.md เป็น latex/chapters/*.tex (เรียกจาก build.sh ใน Docker)
- ใช้ pandoc แปลงเนื้อหา แล้วปรับให้เข้ากับรูปแบบรายงานไทย
- หัวข้อในไฟล์ .md มีเลขบท/ข้อกำกับไว้แล้ว (เช่น "บทที่ 1", "1.1") จึงใช้หัวข้อแบบไม่ใส่เลขซ้ำ แล้วเพิ่มเข้าสารบัญเอง
"""
import pathlib, re, subprocess, sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
SRC, OUT = ROOT / "md", ROOT / "latex" / "chapters"
SKIP = {"00-cover.md"}          # ปกทำใน main.tex

def widen(m):
    """ตารางกว้างตามเนื้อหา: แบ่งความกว้างคอลัมน์ตามความยาวข้อความ ให้ตัดบรรทัดได้ไม่ล้นขอบ"""
    n, body = len(m.group(1)), m.group(2)
    lens = [1] * n
    for row in re.findall(r"^(.*?)\\\\\s*$", body, flags=re.M):
        cells = row.split(" & ")
        if len(cells) != n: continue
        for i, c in enumerate(cells):
            lens[i] = max(lens[i], min(len(re.sub(r"\\[a-zA-Z]+|[{}]", "", c)), 60))
    tot = sum(lens)
    w = [max(0.12, l / tot) for l in lens]; k = sum(w)
    spec = "".join(f">{{\\raggedright\\arraybackslash}}p{{{0.97 * x / k:.3f}\\linewidth - 2\\tabcolsep}}" for x in w)
    return "\\begin{longtable}[]{@{}" + spec + "@{}}" + body + "\\end{longtable}"

def convert(md: pathlib.Path) -> str:
    tex = subprocess.run(
        ["pandoc", str(md), "-f", "gfm", "-t", "latex", "--top-level-division=chapter",
         "--shift-heading-level-by=-1", "--no-highlight", "--columns=50", "--wrap=none"],
        check=True, capture_output=True, text=True).stdout
    tex = re.sub(r"\\hypertarget\{[^}]*\}\{%\n", "", tex)
    def head(m):
        cmd, title = m.group(1), m.group(2).replace("\n", " ").strip()
        toc = {"chapter": "chapter", "section": "section"}.get(cmd)
        return f"\\{cmd}*{{{title}}}" + (f"\\addcontentsline{{toc}}{{{toc}}}{{{title}}}" if toc else "")
    tex = re.sub(r"\\(chapter|section|subsection|subsubsection)\{(.*?)\}\\label\{[^}]*\}\}?", head, tex, flags=re.S)
    tex = re.sub(r"\\includegraphics\{([^}]*)\}",
                 r"\\begin{center}\\includegraphics[width=0.95\\linewidth,keepaspectratio]{\1}\\end{center}", tex)
    tex = re.sub(r"\\begin\{longtable\}\[\]\{@\{\}(l+)@\{\}\}(.*?)\\end\{longtable\}", widen, tex, flags=re.S)
    tex = tex.replace("\\begin{verbatim}", "\\begin{Verbatim}[breaklines,breakanywhere,fontsize=\\small,frame=single,framesep=6pt]")
    tex = tex.replace("\\end{verbatim}", "\\end{Verbatim}")
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
