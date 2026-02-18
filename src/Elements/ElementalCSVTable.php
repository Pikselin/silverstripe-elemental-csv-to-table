<?php

namespace Pikselin\Elemental\Blocks;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\FieldType\DBField;
use function _t;

class ElementalCSVTable extends BaseElement {

    private static $singular_name = 'CSV Table';
    private static $plural_name = 'CSV Tables';
    private static $icon = 'font-icon-thumbnails';
    private static $db = [
        'UseFirstRowAsHeader' => 'Boolean'
    ];
    private static $has_one = [
        'CSVFile' => File::class
    ];
    private static $owns = [
        'CSVFile'
    ];
    private static $table_name = 'ElementalCSVTable';
    private static $inline_editable = true;

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $UseFirstRowAsHeader = CheckboxField::create('UseFirstRowAsHeader', 'Use First Row as Header')->setDescription('This will use the first row of the CSV data as a table header block.');
        $File = UploadField::create('CSVFile', 'CSV File');
        $File->setFolderName('ElementalCSVTables');
        $File->getValidator()->setAllowedExtensions(['csv']);
        $fields->addFieldToTab('Root.Main', LiteralField::create('CSVTableLabel', '<p>Upload a simple CSV format file and have it transformed into a simple HTML table with a responsive container. <strong>The first row will be used as the table header</strong></p>'));
        $fields->addFieldToTab('Root.Main', $File);
        $fields->addFieldToTab('Root.Main', $UseFirstRowAsHeader);
        return $fields;
    }

    public function getType() {
        return _t(__CLASS__ . '.BlockType', 'CSV Table');
    }

    public function getSummary() {
        return DBField::create_field('HTMLText', '<p>Create tabular data from a CSV file.</p>')->Summary(20);
    }

    protected function provideBlockSchema() {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['Content'] = $this->getSummary();
        return $blockSchema;
    }

    public function CSVFileName() {
        return Director::getAbsFile('assets/' . $this->CSVFile()->Filename);
    }

    private function CSVdata() {
        //$config = SiteConfig::current_site_config();
        $table = [
            'header' => [],
            'body' => []
        ];

        if ($this->CSVFile()) {
            $CSVFile = fopen(Director::getAbsFile('assets/' . $this->CSVFile()->Filename), "r");
            if ($CSVFile !== FALSE) {
                $c = 0;
                while (($data = fgetcsv($CSVFile, escape: "")) !== FALSE) {
                    if ($c == 0 && $this->UseFirstRowAsHeader == true) {
                        $num = count($data);
                        foreach ($data as $k => $v) {
                            $table['header'][] = $v;
                        }
                        $c++;
                    } else {
                        $num = count($data);
                        $line = array();

                        $line[0] = $data[0];

                        unset($data[0]);
                        foreach ($data as $k => $v) {
                            $line[] = $v;
                        }
                        $table['body'][] = $line;
                    }
                }
            }
            fclose($CSVFile);
        }


        return $table;
    }

    private function makeHTMLTable() {
        $data = $this->CSVdata();
        if ($data !== false) {
            $headerRows = '';
            $bodyRows = '';
            foreach ($data as $k => $v) {
                if ($k == 'header') {
                    $headerRows .= '<tr>';
                    foreach ($v as $khead => $vhead) {
                        $headerRows .= '<th>' . $vhead . '</th>';
                    }
                    $headerRows .= '</tr>';
                } else if ($k == 'body') {
                    foreach ($v as $krow => $vrow) {
                        $bodyRows .= '<tr>';
                        foreach ($vrow as $kbody => $vbody) {
                            $bodyRows .= '<td>' . $vbody . '</td>';
                        }
                        $bodyRows .= '</tr>';
                    }
                }
            }

            $bodyContainer = '<table class="embeddedDataTable"><thead>{TABLEHEADER}</thead><tbody>{TABLEBODY}</tbody></table>';
            //echo 'header '.$this->StickyHeader;

            return str_replace(
                    ['{TABLEHEADER}', '{TABLEBODY}'],
                    [$headerRows, $bodyRows],
                    $bodyContainer
            );
        }
        return false;
    }

    public function renderCSVasTable() {
        return DBField::create_field('HTMLText', $this->makeHTMLTable());
    }

}
