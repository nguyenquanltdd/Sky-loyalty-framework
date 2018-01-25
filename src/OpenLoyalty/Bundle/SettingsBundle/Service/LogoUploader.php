<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Service;

use Gaufrette\Filesystem;
use OpenLoyalty\Bundle\SettingsBundle\Model\Logo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class LogoUploader.
 */
class LogoUploader
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * FileUploader constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param Logo $photo
     *
     * @return string|null
     */
    public function get(Logo $photo)
    {
        if (null === $photo || null === $photo->getPath()) {
            return;
        }

        return $this->filesystem->get($photo->getPath())->getContent();
    }

    /**
     * @param UploadedFile $src
     *
     * @return Logo
     */
    public function upload(UploadedFile $src)
    {
        $file = new Logo();
        $fileName = md5(uniqid()).'.'.$src->guessExtension();
        $file->setPath('logo'.DIRECTORY_SEPARATOR.$fileName);
        $file->setMime($src->getClientMimeType());
        $file->setOriginalName($src->getClientOriginalName());

        $this->filesystem->write($file->getPath(), file_get_contents($src->getRealPath()));
        unlink($src->getRealPath());

        return $file;
    }

    /**
     * @param Logo|null $file
     */
    public function remove(Logo $file = null)
    {
        if (null === $file || null === $file->getPath()) {
            return;
        }

        $path = $file->getPath();
        if ($this->filesystem->has($path)) {
            $this->filesystem->delete($path);
        }
    }
}
