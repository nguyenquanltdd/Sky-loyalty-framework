<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OpenLoyalty\Bundle\SettingsBundle\Model\Logo;

/**
 * Class FileSettingEntry.
 *
 * @ORM\Entity()
 */
class FileSettingEntry extends SettingsEntry
{
    /**
     * @var array
     * @ORM\Column(type="json_array", name="json_value")
     */
    protected $value = [];

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        if (!$value instanceof Logo) {
            return;
        }

        $this->value = [
            'path' => $value->getPath(),
            'originalName' => $value->getOriginalName(),
            'mime' => $value->getMime(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $logo = new Logo();

        if (array_key_exists('path', $this->value)
            && array_key_exists('path', $this->value)
            && array_key_exists('path', $this->value)
        ) {
            $logo->setPath($this->value['path']);
            $logo->setOriginalName($this->value['originalName']);
            $logo->setMime($this->value['mime']);
        }

        return $logo;
    }
}
