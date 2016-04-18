<?php

/*
 * Copyright (C) 2016 Matthew McNaney <mcnaneym@appstate.edu>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace phpws;

/**
 * Description of Form_Multiple
 */
class Form_Multiple extends Form_Element
{

    public $type = 'multiple';
    public $isArray = true;
    public $match = null;
    public $optgroup = null;

    public function get()
    {
        $content[] = '<select ' . $this->getName(true) . 'multiple="multiple" '
                . $this->getData()
                . $this->getWidth(true)
                . $this->getDisabled()
                . '>';
        foreach ($this->value as $value => $label) {
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }

            if ($this->optgroup && isset($this->optgroup[$value])) {
                if (isset($current_opt)) {
                    $content[] = '</optgroup>';
                }
                $current_opt = $value;
                $content[] = sprintf('<optgroup label="%s">', $this->optgroup[$value]);
            }


            if ($this->isMatch($value)) {
                $content[] = sprintf('<option value="%s" selected="selected">%s</option>', $value, $label);
            } else {
                $content[] = sprintf('<option value="%s">%s</option>', $value, $label);
            }
        }
        if (isset($current_opt)) {
            $content[] = '</optgroup>';
        }

        $content[] = '</select>';

        return implode("\n", $content);
    }

    public function setMatch($match)
    {
        if (!is_array($match)) {
            $this->match[] = $match;
        } else {
            $this->match = $match;
        }
    }

    public function setOptgroup($value, $label)
    {
        $this->optgroup[$value] = $label;
    }

    public function isMatch($match)
    {
        if (!isset($this->match)) {
            return false;
        }

        return (in_array($match, $this->match)) ? true : false;
    }

}
