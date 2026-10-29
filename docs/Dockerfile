# สร้างไฟล์ PDF ของรายงานจาก Markdown → LaTeX → PDF
# docker build -t cs-buu-report .
# docker run --rm -v "$PWD":/work cs-buu-report      (Windows PowerShell: -v "${PWD}:/work")
FROM ubuntu:24.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends \
      texlive-xetex texlive-latex-recommended texlive-latex-extra texlive-fonts-recommended \
      fonts-thai-tlwg pandoc python3 make \
 && rm -rf /var/lib/apt/lists/*
WORKDIR /work
CMD ["bash", "build.sh"]
