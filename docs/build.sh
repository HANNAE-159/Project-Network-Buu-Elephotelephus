#!/usr/bin/env bash
# รันใน Docker: แปลง md/*.md → latex/chapters/*.tex แล้วคอมไพล์เป็น report.pdf
set -euo pipefail
cd "$(dirname "$0")"
echo "1/2 แปลง Markdown เป็น LaTeX"
python3 tools/md2tex.py
echo "2/2 คอมไพล์ด้วย XeLaTeX (2 รอบ เพื่อให้สารบัญครบ)"
cd latex
for i in 1 2; do xelatex -interaction=nonstopmode -halt-on-error main.tex > /dev/null; done
cp main.pdf ../report.pdf
echo "เสร็จแล้ว: docs/report.pdf ($(du -h ../report.pdf | cut -f1))"
