<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface FileInterface.
 */
interface FileInterface
{
    /**
     * @return null|UploadedFile
     */
    public function getFile() : ? UploadedFile;

    /**
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file) : void;

    /**
     * @return null|string
     */
    public function getPath() : ? string;

    /**
     * @param string $path
     */
    public function setPath(string $path) : void;

    /**
     * @return null|string
     */
    public function getOriginalName() : ? string;

    /**
     * @param string $originalName
     *
     * @return mixed
     */
    public function setOriginalName(string $originalName);

    /**
     * @return null|string
     */
    public function getMime() : ? string;

    /**
     * @param string $mime
     */
    public function setMime(string $mime) : void;

    /**
     * @param array $data
     *
     * @return FileInterface
     */
    public static function deserialize(array $data = []) : FileInterface;
}
