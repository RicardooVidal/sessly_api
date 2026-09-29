<?php

namespace App\Services;

use App\Data\Appointment\ImportMedicalRecordData;
use DOMDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class PhpWordService
{
    private ?string $html = null;
    private ?string $filePath = null;

    public function setFilePathByUpload(UploadedFile $file): self
    {
        $this->filePath = $file->getRealPath();

        return $this;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function convertToHtml(): self
    {
        $phpWord = IOFactory::load($this->filePath);
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($tempFile);
        $html = file_get_contents($tempFile);

        unlink($tempFile);

        $this->html = $html;

        return $this;
    }

    public function extractBody(): self
    {
        if (!$this->html) {
            return $this;
        }

        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $this->html, $matches)) {
            $this->html = $matches[1];
        }

        return $this;
    }

    public function normalizeHtml(): self
    {
        if (!$this->html) {
            return $this;
        }

        // Converte font-weight:bold do PHPWord para <strong>
        $this->html = preg_replace_callback(
            '/<span([^>]*)style="([^"]*font-weight:\s*bold;?[^"]*)"([^>]*)>(.*?)<\/span>/is',
            function ($matches) {
                $before = $matches[1];
                $style = $matches[2];
                $after = $matches[3];
                $content = $matches[4];

                $style = preg_replace(
                    '/font-weight:\s*bold;?/i',
                    '',
                    $style
                );

                $style = trim($style);

                if ($style) {
                    return sprintf(
                        '<span%s style="%s"%s><strong>%s</strong></span>',
                        $before,
                        $style,
                        $after,
                        $content
                    );
                }

                return "<strong>$content</strong>";
            },
            $this->html
        );

        return $this;
    }

    /**
     * @throws Exception
     */
    public function convertToDocx(string $uuid, string $medicalRecordEncrypted): string
    {
        $html = base64_decode($medicalRecordEncrypted);

        $phpWord = new PhpWord();

        $section = $phpWord->addSection();

        Html::addHtml(
            $section,
            $html,
            false,
            false
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'medical_record_');

        $writer = IOFactory::createWriter($phpWord);

        $writer->save($tempFile);

        Storage::put("medical_records/$uuid.html", $html);
        Storage::put("medical_records/$uuid.docx", file_get_contents($tempFile));

        unlink($tempFile);

        return "medical_records/$uuid.docx";
    }

    public function getContent(): ?string
    {
        return $this->html;
    }
}
