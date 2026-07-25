<?php

namespace App\Services\StudyPrograms;

use Generator;
use RuntimeException;
use XMLReader;
use ZipArchive;

class XlsxRowReader
{
    public function rows(string $file): Generator
    {
        $zip = new ZipArchive();

        if ($zip->open($file) !== true) {
            throw new RuntimeException('فایل اکسل قابل خواندن نیست: '.$file);
        }

        $shared = $this->sharedStrings($zip);
        $rels = $this->relations($zip);
        $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
        $sheets = $workbook?->xpath('//*[local-name()="sheet"]') ?: [];

        foreach ($sheets as $sheet) {
            $attrs = $sheet->attributes('r', true);
            $path = $rels[(string) $attrs['id']] ?? null;

            if (! $path) {
                continue;
            }

            yield from $this->sheetRows($file, (string) $sheet['name'], $path, $shared);
        }

        $zip->close();
    }

    private function sheetRows(string $file, string $sheetName, string $sheetPath, array $shared): Generator
    {
        $reader = new XMLReader();
        $source = 'zip://'.str_replace('\\', '/', realpath($file)).'#'.$sheetPath;

        if (! $reader->open($source)) {
            return;
        }

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }

            $rowNumber = (int) $reader->getAttribute('r');
            $row = [];

            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'c') {
                    $row[$this->columnIndex((string) $reader->getAttribute('r'))] = trim($this->cellValue($reader, $shared, $reader->getAttribute('t')));
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'row') {
                    break;
                }
            }

            yield ['sheet' => $sheetName, 'row' => $rowNumber, 'values' => $row];
        }

        $reader->close();
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = [];
        $reader = new XMLReader();
        $reader->XML($xml);

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $strings[] = trim(strip_tags($reader->readOuterXml()));
            }
        }

        $reader->close();

        return $strings;
    }

    private function relations(ZipArchive $zip): array
    {
        $xml = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
        $rels = [];

        foreach (($xml?->xpath('//*[local-name()="Relationship"]') ?: []) as $rel) {
            $target = (string) $rel['Target'];
            $rels[(string) $rel['Id']] = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
        }

        return $rels;
    }

    private function cellValue(XMLReader $reader, array $shared, ?string $type): string
    {
        $value = '';

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && in_array($reader->localName, ['v', 't'], true)) {
                $value = $reader->readString();
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'c') {
                break;
            }
        }

        return $type === 's' ? ($shared[(int) $value] ?? '') : $value;
    }

    private function columnIndex(string $cellReference): int
    {
        preg_match('/^[A-Z]+/', $cellReference, $matches);
        $index = 0;

        foreach (str_split($matches[0] ?? '') as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }
}
