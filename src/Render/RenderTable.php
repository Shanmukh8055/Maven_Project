<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AReportDpmXBRL\Render;

use AReportDpmXBRL\Domain\Domain;
use AReportDpmXBRL\HTMLTable\HTMLTable;
use AReportDpmXBRL\Library\Directory;
use AReportDpmXBRL\Library\DomToArray;
use AReportDpmXBRL\Library\Format;

/**
 * Class RenderTable
 * @category
 * Areport @package AReportDpmXBRL\Config
 * @author Fuad Begic <fuad.begic@gmail.com>
 * Date: 12/06/2020
 */
class RenderTable
{

    //put your code here

    private $specification;
    private $row = array();
    private $col = array();
    private $breakdownTreeArc;
    private $import;
    private $ext;
    private $axis;
    private $lang;
    private $sheet;
    private $roleType = array();

    private function setImport($import)
    {

        if (!is_null($import)):
            $this->import = $import['file'];
            $this->ext = $import['ext'];
            $this->sheet = $import['sheets'];
        endif;
    }

    private function setLang($lang)
    {
        if (!is_null($lang)):
            $this->lang = $lang;
        endif;
    }

    public function renderHtml($xbrl, $import = NULL, $lang = NULL, $additional = array())
    {

        $this->specification = $xbrl;
        $this->roleType = array_keys($this->specification['def']);

        $this->setImport($import);
        $this->setLang($lang);

        $att = ['class' => 'table table-bordered table-hover   xbrl_table', 'id' => 'table'];


        $table = new HTMLTable($att);
        $head = &$table->getHeader();
        $body = &$table->getBody();
        $table->setAutoGrow(true);
        $table->setAutoFill('');
        $sheetsHtml = NULL;
        $aspectNode = NULL;
        $explicitDimension = NULL;

        $this->axis = new Axis($this->specification, $this->lang);


        $tableNameId = key($this->specification['rend']['table']);

        $tableLabelName = $this->specification['rend']['table'][$tableNameId]['label'];

        $tableID =
            $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $tableLabelName, 'http://www.eba.europa.eu/xbrl/role/dpm-db-id');


        $tableName = $this->axis->searchLabel($tableLabelName, 'http://www.xbrl.org/2008/role/label');
        $labelFullName = $this->axis->searchLabel($tableLabelName, 'http://www.xbrl.org/2008/role/verboseLabel');

        $this->breakdownTreeArc =
            $this->axis->searchLabel($tableNameId, 'http://xbrl.org/arcrole/PWD/2013-05-17/table-breakdown');


        foreach ($this->breakdownTreeArc as $key => $row):
            switch ($key):

                case 'x':


                    $header =
                        $this->axis->buildXAxis($this->specification['rend']['definitionNodeSubtreeArc'], key($row));

                    break;

                case 'y':


                    if (isset($this->specification['rend']['aspectNode'][key($row)])):

                        $aspectNode = TRUE;
                        $contents = $this->specification['rend']['aspectNode'];

                    else:
                        $contents =
                            $this->axis->buildYAxis($this->specification['rend']['definitionNodeSubtreeArc'], key($row));
                    endif;
                    break;

                case 'z':

                    if (isset($this->specification['rend']['aspectNode'][key($row)]) && isset($this->specification['rend']['aspectNodeFilterArc'])):

                        $explicitDimension =
                            $this->specification['rend']['explicitDimension'][$this->specification['rend']['aspectNodeFilterArc'][key($row)]['to']];

                        $domain =
                            Domain::getDomain($explicitDimension['linkrole'], Directory::getRootName($this->specification['tab_xsd_uri']));

                        $sheetsHtml = $this->explicitDimensionSheets($domain, $explicitDimension['dimension']);

                    else:

                        $sheets =
                            $this->axis->buildZAxis($this->specification['rend']['definitionNodeSubtreeArc'], key($row));
                        $sheetsHtml = $this->showSheets($sheets);

                    endif;
                    break;

            endswitch;

        endforeach;


        // max depth rowspan
        $rowspanMax = $colspanMax = max(array_column($header, 'row')) + 1;

        // add two columns for the rc code on the y axis
        if (!empty($contents) && !isset($aspectNode)):
            $colspanMax = max(array_column($contents, 'col')) + 2;
        elseif (isset($aspectNode)):
            $colspanMax = count($this->specification['rend']['aspectNode']);
        endif;


        $col = 0;
        $storPosition = array();

        $head->setHeaderContents(0, 0, $this->axis->searchLabel($tableNameId, 'http://www.xbrl.org/2008/role/label'), array('colspan' => $colspanMax, 'class' => 'xbrl-title', 'rowspan' => ((!is_null($aspectNode)) ? $rowspanMax : $rowspanMax + 1)));

        $head->setHeaderContents(1, 0, $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $tableNameId, 'http://www.xbrl.org/2008/role/verboseLabel'), array('colspan' => $colspanMax, 'rowspan' => ((!is_null($aspectNode)) ? $rowspanMax - 1 : $rowspanMax), 'class' => 'xbrl-th'));

        $keys = array_keys($header);

        //X axis
        foreach (array_keys($keys) as $row):
            if (isset($keys[$row - 1])):
                $prev = $header[$keys[$row - 1]];
            endif;
            $this_value = $header[$keys[$row]];


            if (isset($storPosition[$this_value['row']])) {

                // check if the previous position is higher or lower
                if ($storPosition[$this_value['row']] >= $storPosition[$prev['row']]):
                    $col = $storPosition[$this_value['row']] + 1;
                elseif (isset($col) && $this_value['row'] == 0 && $this_value['abstract'] == 'false'):
                    $col = $col + 1;
                endif;


                // Save position, if the position has a child elelemt then it is an indent for the number of child elements
                $tmpPos = NULL;
                if (isset($this_value['leaves_element'])):
                    $tmpPos = $col + $this_value['leaves_element'] - 1;
                elseif (isset($this_value['metric_element'])):
                    $tmpPos = $col + $this_value['metric_element'] - 1;
                else:
                    $tmpPos = $col;
                endif;


                $storPosition[$this_value['row']] = $tmpPos;
            } else {


                $tmpPos = NULL;
                // If the position is not set and has child elements, set it to the number of child elements plus the number of columns, otherwise only to the number of columns

                if (isset($this_value['leaves_element'])):

                    // if the position has childe elements and if the parent element is filled or has a metric value
                    $tmpPos = $col + $this_value['leaves_element'] - 1;
                elseif (isset($this_value['metric_element'])):
                    // if the position has childe elements just consider the number of metrics
                    $tmpPos = $col + $this_value['metric_element'] - 1;
                else:
                    $tmpPos = $col;
                endif;


                $storPosition[$this_value['row']] = $tmpPos;
            }


            //Rc-code
            $this->col[$col]['rc-code'] = $rcCode =
                $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $this_value['to'], 'http://www.eurofiling.info/xbrl/role/rc-code');
            $this->col[$col]['id'] = $this_value['to'];
            $this->col[$col]['abstract'] = $this_value['abstract'];


            $lebelName = $this->axis->searchLabel($this_value['to'], 'http://www.xbrl.org/2008/role/label');
            if (isset($this_value['leaves_element']) && $this_value['abstract'] != 'true'):

                $head->setHeaderContents($this_value['row'], $col + $colspanMax, (empty($lebelName) ? $this_value['to'] : $lebelName), array('data-id' => $this_value['to'],
                    'class' => 'xbrl-th xbrl-none-bottom',
                    'y' => $this_value['row'],
                    'x' => $col,
                    'colspan' => isset($this_value['rollup']) ? $this_value['leaves_element'] : $this_value['leaves_element'] + 1,
                    'metric' => $this_value['metric'],
                    'dimension' => json_encode($this_value['dimension']),
                ));


                $head->setHeaderContents($this_value['row'] + 1, $col + $colspanMax, '', array('data-id' => $this_value['to'],
                    'class' => 'xbrl-th xbrl-none-top',
                    'rowspan' => $rowspanMax - $this_value['row'] - 1,
                ));

                $head->setHeaderContents($rowspanMax, $col + $colspanMax, $rcCode, array('class' => 'rc-code', 'data-col' => $col));


                $col = $col + 1;
            else:
                //
                if (isset($this_value['metric_element']) && $this_value['metric_element'] != 0 && $this_value['metric_element'] < $this_value['leaves_element']):
                    $this_value['leaves_element'] = $this_value['metric_element'];
                endif;

                $head->setHeaderContents($this_value['row'], $col + $colspanMax, (empty($lebelName) ? $this_value['to'] : $lebelName), array('data-id' => $this_value['to'],
                    'class' => 'xbrl-th',
                    // 'y' => $this_value['row'],
                    //'x' => $col,
                    'rowspan' => (isset($this_value['leaves_element']) || ($rowspanMax - 1) == $this_value['row']) ? 1 : $rowspanMax - $this_value['row'],
                    'colspan' => (isset($this_value['leaves_element']) ? $this_value['leaves_element'] : "0"),
                    //'metric' => ($this_value['metric'] == 'false') ? '' : $this_value['metric'],
                    //'dimension' => json_encode($this_value['dimension']),
                ));
                //postoji bug kod setovanja colspan-a i rowspan-a

                $head->setHeaderContents($rowspanMax, $col + $colspanMax, $rcCode, array('class' => 'rc-code', 'data-col' => $col));


            endif;


        endforeach;

        //Y axis

        $len = count($contents);
        $y = 0;
        if (!isset($aspectNode)):
            foreach ($contents as $key => $row):

                if (true): //$row['abstract'] != 'true'
                    //echo "<pre>", print_r($row), "</pre>";
                    $labelName = $this->axis->searchLabel($row['to'], 'http://www.xbrl.org/2008/role/label');
                    $this->row[$y]['rc-code'] = $rcCode =
                        $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $row['to'], 'http://www.eurofiling.info/xbrl/role/rc-code');
                    $this->row[$y]['id'] = $row['to'];
                    $this->row[$y]['abstract'] = $row['abstract'];


                    //set rc-code
                    if ($row['abstract'] != 'true'):
                        $body->setCellContents($y, 0, $rcCode);
                        $body->setCellAttributes($y, 0, array('class' => 'rc-code'));
                    else:
                        $body->setCellAttributes($y, 0, array('class' => 'xbrl-none-left xbrl-none-right', 'rc-code' => $rcCode));
                    endif;
                    // remove border
                    if ($row['col'] > 0):

                        for ($i = 0; $i < $row['col']; $i++):
                            $body->setCellAttributes($y, $i + 1, array('class' => 'xbrl-none-left xbrl-none-right'));

                        endfor;
                    endif;

                    $bold = FALSE;

                    if ($y != $len - 1):
                        $bold =
                            ($row['col'] < $contents[$y + 1]['col'] && $contents[$y + 1]['abstract'] != 'true') ? TRUE : FALSE;
                    endif;

                    $body->setCellContents($y, 1 + $row['col'], (empty($labelName)) ? $row['to'] : $labelName);
                    $body->setCellAttributes($y, 1 + $row['col'], array(
                        'data-id' => $row['to'],
                        'colspan' => $colspanMax - $row['col'] - 1,
                        'class' => ($row['abstract'] == 'true' || $bold) ? 'xbrl-bold xbrl-none-left' : 'xbrl-none-left',
                        //'metric' => ($row['metric'] == 'false') ? '' : $row['metric'],
                        //   'dimension' => json_encode($row['dimension']),
                    ));
                    $y++;
                endif;
            endforeach;
        else:
            foreach ($this->specification['rend']['aspectNode'] as $aspect):

                $from =
                    $this->axis->searchLabel($aspect['id'], 'http://xbrl.org/arcrole/PWD/2013-05-17/breakdown-tree');

                $this->row[$y]['rc-code'] = $rcCode =
                    $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $from, 'http://www.eurofiling.info/xbrl/role/rc-code');
                $this->row[$y]['labelName'] =
                    $this->axis->searchLabel($from, 'http://www.xbrl.org/2008/role/label');

                $this->row[$y]['id'] = $aspect['id'];
                $this->row[$y]['axis'] = 'y';

                $y++;
            endforeach;

            $this->col = array_merge($this->row, $this->col);
            //Sortiranje po rc-codu
            //$this->col = array_merge($this->col, $this->row);
            // usort($this->col, array($this, "cmp"));
        endif;

        ###############XY###############
        if (!is_null($aspectNode)):
            /* Open table */


            $maxRow = $this->axis->getMaxRow($this->import);


            $node = ($this->specification['rend']['aspectNode']);


            for ($y = 0; $y < $maxRow; $y++):
                $x = 0;
                $abstractCol = 0;
                foreach ($this->col as $col) {

                    //  dump($col);

                    $name = 'c' . $col['rc-code'] . 'r' . ($y + 1);

                    $typ = array();


                    //AspectNode
                    if (isset($node[$col['id']])):

                        $yN = $node[$col['id']];
                        if (isset($this->specification['rend']['aspectNodeFilterArc'][$col['id']])):

                            $explicitDimension =
                                $this->specification['rend']['explicitDimension'][$this->specification['rend']['aspectNodeFilterArc'][$col['id']]['to']];


                            $additional['explicitDimension'] =
                                Domain::getDomain($explicitDimension['linkrole'], Directory::getRootName($this->specification['tab_xsd_uri']));
                            $additional['dimension'] = $explicitDimension['dimension'];

                        else:

                            unset($additional['explicitDimension']);
                            $IDtyp = Format::getAfterSpecChar($yN['dimensionAspect'], ':');

                            foreach ($this->roleType as $row):
                                $defArr = DomToArray::search_multdim($this->specification['def'][$row], 'name', $IDtyp);

                                if (is_array($defArr)):
                                    $defArr = end($defArr);
                                    $typ['typ'] = $defArr['typ']['key'] . ':' . $defArr['typ']['name'];

                                    if (!empty($typ)):
                                        break;
                                    endif;

                                endif;
                            endforeach;
                        endif;

                    else:
                        $yN = $node;
                    endif;


                    $dim =
                        $this->axis->mergeDimensions(DomToArray::search_multdim($header, 'to', $col['id']), $yN, $typ);


                    $def = $this->axis->checkDef($dim);

                    $input = $this->getType($name, $def, $dim, $additional);

                    if (isset($col['labelName'])):

                        $head->setHeaderContents($rowspanMax, $x, $col['labelName'], array('class' => 'xbrl-title', 'data-col' => $abstractCol));
                    endif;
                    $abstractCol++;
                    $body->setCellContents($y, $x, $input);
                    $body->setCellAttributes($y, $x, array(
                        'class' => 'xbrl-td',
                        'rc-code' => $name,
                        //  'dimension' => $dim,
                    ));
                    $x++;
                }
            endfor;
        else:
            $x = $y = 0;
            foreach ($this->col as $col) {
                $y = 0;
                foreach ($this->row as $row) {


                    $name = 'c' . $col['rc-code'] . 'r' . $row['rc-code'];

                    $dim =
                        $this->axis->mergeDimensions(DomToArray::search_multdim($header, 'to', $col['id']), DomToArray::search_multdim($contents, 'to', $row['id']));


                    $def = $this->axis->checkDef($dim);

                    $disabled =
                        ($def && $row['abstract'] != 'true' && $col['abstract'] != 'true') ? '' : 'disabled';


                    if ($disabled !== 'disabled'):

                        $input = $this->getType($name, $def, $dim, $additional);
                        $body->setCellContents($y, $colspanMax + $x, $input);
                        $body->setCellAttributes($y, $colspanMax + $x, array(
                            'class' => 'xbrl-td',
                            //'dimension' => $dim,
                        ));

                    elseif ($row['abstract'] == 'true'):
                        $body->setCellContents($y, $colspanMax + $x, '');
                        $body->setCellAttributes($y, $colspanMax + $x, array('class' => 'xbrl-none-left xbrl-none-right'
                            //'dimension' => $dim,
                        ));
                    else:

                        $body->setCellContents($y, $colspanMax + $x, '');
                        $body->setCellAttributes($y, $colspanMax + $x, array(
                            'class' => 'xbrl-td', 'bgcolor' => '#808080'
                            //'dimension' => $dim,
                        ));
                    endif;


                    $y++;
                }
                $x++;
            }
        endif;


        return array('sheets' => $sheetsHtml, 'table' => $table->toHtml(), 'tableName' => $tableName, 'aspectNode' => $aspectNode, 'table', 'tableID' => $tableID);
    }

    private function showSheets($sheets)
    {
        $html = NULL;

        $html .= "<select class='selectpicker' data-show-icon='true' id='sheet' name='sheet' >";
        $shee = 1;

        foreach ($sheets as $sheet):

            $label = $this->axis->searchLabel($sheet['to'], 'http://www.xbrl.org/2008/role/label');
            $rccode =
                $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $sheet['to'], 'http://www.eurofiling.info/xbrl/role/rc-code');
            $selected =
                isset($this->sheet[$rccode]) && $this->sheet[$rccode] == 'active' ? 'selected data-icon=\'fas fa-file-alt\'' : '';

            $exist =
                isset($this->sheet[$rccode]) && $this->sheet[$rccode] == 'found' ? "data-icon='fas fa-file-alt'" : "";
            $html .= "<option id='$rccode' data-id='$rccode'  $selected  $exist value=" . json_encode(array_merge($sheet['dimension'], ['sheet' => $rccode])) . ">$label</option>";
            //$html .= "<option  value=" . $rccode . ">$label</option>";
            $shee++;
        endforeach;
        $html .= "</select>";
        return $html;
    }

    private function explicitDimensionSheets($sheets, $dimension)
    {


        $html = NULL;
        $dim = key($dimension);

        $dom = strtok($dimension[$dim], ':');


        $html .= "<select class='selectpicker' data-show-icon='true' id='sheet' name='sheet' >";
        $shee = 1;

        foreach ($sheets as $sheet):

            // $label = $this->axis->searchLabel($sheet['to'], 'http://www.xbrl.org/2008/role/label');
            // $rccode = $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $sheet['to'], 'http://www.eurofiling.info/xbrl/role/rc-code');
            //  $selected = isset($this->sheet[$rccode]) && $this->sheet[$rccode] == 'active' ? 'selected' : '';
            $id = substr($sheet['href'], strpos($sheet['href'], "#") + 1);
            $keyID = substr($id, strpos($id, "_") + 1);
            $key[$dim] = $dom . ':' . $keyID;

            $exist =
                isset($this->sheet[$keyID]) && $this->sheet[$keyID] == 'found' ? "data-icon='fa-table'" : "";
            $selected = isset($this->sheet[$keyID]) && $this->sheet[$keyID] == 'active' ? 'selected' : '';

            $html .= "<option $exist $selected id=" . $keyID . " data-id='$keyID' value='" . json_encode(array_merge($key, ['sheet' => $keyID])) . "' >" . $sheet['@content'] . "</option>";

            $shee++;
        endforeach;
        $html .= "</select>";
        return $html;
    }

    private function getValue($name, $type, $dim)
    {

        if (isset($this->import[$name])):
            $value = FALSE;
            switch ($this->ext):
                case 'xbrl':
                    $value = $this->searchInstanceValue($dim);
                    break;
                case 'DB':

                    if ($type == 'xbrli:monetaryItemType'):
                        $value = $this->import[$name]['integer'];
                    elseif ($type == 'num:percentItemType'):
                        $value = $this->import[$name]['string'];
                    elseif ($type == 'xbrli:QNameItemType'):
                        $value = $this->import[$name]['string'];
                    else:
                        $value = $this->import[$name]['string'];
                    endif;

                    break;
                case 'xlsx':
                    $value = $this->import[$name];
                    break;


            endswitch;
            return $value;
        endif;
    }

    private function searchInstanceValue($dim)
    {

        foreach ($this->import as $row):

            if (isset($row['dimension'])):
                $tmp = array_diff_assoc(json_decode($row['dimension'], true), json_decode($dim, true));

                if (empty($tmp)):
                    return substr($row['value'], 0, $row['decimals']);
                endif;
            endif;

        endforeach;
    }

    private function getType($name, $def, $dim, $additional)
    {
        $_dim = json_decode($dim, true);


        //$readonly = 'readonly';


        $input = "<input  name='" . $name . "[dim]' value='$dim' type='hidden' />";

        if (isset($def['type_metric'])):
            $value = $this->getValue($name, $def['type_metric'], $dim);

            switch ($def['type_metric']):

                case 'xbrli:monetaryItemType':
                    $input .= "<input  " . ((isset($readonly)) ? $readonly : "") . " id='$name' title='Upišite cjelobrojnu vrijednost'  name='" . $name . "[value]' type='number'  value='$value' class='xbrl-input' />";
                    break;
                case 'xbrli:QNameItemType':
                case 'enum:enumerationItemType':
                    if (isset($def['presentation'])):

                        $input .= "<select class='xbrl-select' id='$name' name='" . $name . "[value]'  oninvalid=\"this.setCustomValidity('Molim, odaberite stavku sa liste')\" oninput=\"setCustomValidity('')\">";
                        $input .= "<option value=''></option>";
                        foreach ($def['presentation'] as $row):

                            $input .= "<option " . (($this->axis->getHierKey($def['namespace'], $row['href']) == $value) ? "selected='selected'" : "") . " value='" . $this->axis->getHierKey($def['namespace'], $row['href']) . "'>" . $row['@content'] . "</option>";
                        endforeach;
                        $input .= "</select>";
                    endif;
                    break;
                case 'num:percentItemType':
                    $input .= "<input  " . ((isset($readonly)) ? $readonly : "") . "  step='.01' id='$name' pattern='[0-9]+(,[0-9]{2})%' title='Procenat mora biti u sljedećem formatu (npr. 10,00%) sa dva decimalna mjesta'  name='" . $name . "[value]' type='text'  value='$value' class='xbrl-input percent' />";
                    break;
                case 'xbrli:booleanItemType':

                    $input .= "<select class='xbrl-select' id='$name' name='" . $name . "[value]''>";
                    $input .= "<option></option>";

                    $input .= "<option " . (('true' === $value) ? "selected='selected'" : "") . " value='true'>Yes</option>";
                    $input .= "<option " . (('false' === $value) ? "selected='selected'" : "") . " value='false'>No</option>";
                    $input .= "</select>";

                    break;

                case 'xbrli:stringItemType':

                    $input .= "<input  " . ((isset($readonly)) ? $readonly : "") . " id='$name'  name='" . $name . "[value]' type='text'  value='$value' class='xbrl-input-text' />";
                    break;
                case 'xbrli:dateItemType':

                    $input .= "<input  " . ((isset($readonly)) ? $readonly : "") . " id='$name'  name='" . $name . "[value]' type='text'  value='$value' class='xbrl-input datepicker' />";
                    break;
                case 'xbrli:decimalItemType':

                    $input .= "<input  " . ((isset($readonly)) ? $readonly : "") . " id='$name'  name='" . $name . "[value]' type='text'  value='$value' class='xbrl-input-decimal' />";
                    break;

                default :
                    $input .= "<input  " . ((isset($readonly)) ? $readonly : "") . " id='$name'  name='" . $name . "[value]' type='text'  value='$value' class='xbrl-input-text' />";
            endswitch;


            return $input;
        elseif (current($_dim) === '*'):

            if (isset($additional['explicitDimension'])):

                $value = FALSE;
                if (isset($def['type_metric'])):
                    $value = $this->getValue($name, $def['type_metric'], $dim);
                endif;

                $input = NULL;
                $key = array();

                $input = "<input  name='" . $name . "[dim]' value='$dim' type='hidden' />";

//fix $dis = (key(json_decode($dim)) == 'fba_dim:VDI') ? 'disabled' : ''; -- treba brisat nije xbrl specifikacija
                $dis = (key(json_decode($dim)) == 'fba_dim:VDI') ? 'disabled' : '';

                $input .= "<select  $dis class='xbrl-select' id='$name' name='" . $name . "[value]' oninvalid=\"this.setCustomValidity('Molim, odaberite stavku sa liste')\" oninput=\"setCustomValidity('')\" >";

                $input .= "<option></option>";

                foreach ($additional['explicitDimension'] as $row):


                    if (!empty($row['order'])):
                        $key[key($_dim)] =
                            Format::getBeforeSpecChar(current($additional['dimension']), ':') . ':' . Format::getAfterSpecChar($row['href'], '#');

                        $selected = $value == json_encode($key) ? 'selected' : '';

                        $input .= "<option $selected value='" . json_encode($key) . "'>" . $row['@content'] . "</option>";

                    endif;
                endforeach;
                $input .= "</select>";

            else:

                $value = $this->getValue($name, 'open', $dim);
                $input .= "<input  " . ((isset($readonly)) ? $readonly : "") . " id='$name'  name='" . $name . "[value]'  value='$value' class='xbrl-input-open' />";

            endif;
            return $input;

        elseif ($def === false):
            $input .= "<input  disabled name='$name' type='text' class='xbrl-input' />";
            return $input;
        endif;
    }

    private function cmp($a, $b)
    {
        if ($a['rc-code'] == $b['rc-code']) {
            return 0;
        }
        return ($a['rc-code'] < $b['rc-code']) ? -1 : 1;
    }

}
