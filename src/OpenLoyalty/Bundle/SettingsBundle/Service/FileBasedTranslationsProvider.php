<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Service;

use Gaufrette\Exception\FileAlreadyExists;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use OpenLoyalty\Bundle\SettingsBundle\Entity\SettingsEntry;
use OpenLoyalty\Bundle\SettingsBundle\Exception\AlreadyExistException;
use OpenLoyalty\Bundle\SettingsBundle\Exception\NotExistException;
use OpenLoyalty\Bundle\SettingsBundle\Model\TranslationsEntry;

/**
 * Class FileBasedTranslationsProvider.
 */
class FileBasedTranslationsProvider implements TranslationsProvider
{
    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected $fallbackFile;

    /**
     * @var array
     */
    protected $ignoredFiles = ['.gitkeep'];

    /**
     * FileBasedTranslationsProvider constructor.
     *
     * @param SettingsManager $settingsManager
     * @param Filesystem      $filesystem
     * @param $fallbackFile
     */
    public function __construct(SettingsManager $settingsManager, Filesystem $filesystem, $fallbackFile = null)
    {
        $this->settingsManager = $settingsManager;
        $this->filesystem = $filesystem;
        $this->fallbackFile = $fallbackFile;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentTranslations()
    {
        $name = $this->settingsManager->getSettingByKey('defaultFrontendTranslations');
        if (!$name instanceof SettingsEntry) {
            if (!$this->fallbackFile) {
                throw new \InvalidArgumentException('defaultFrontendTranslations setting must be set');
            }
            $name = $this->fallbackFile;
        } else {
            $name = $name->getValue();
        }

        if (!$this->filesystem->has($name)) {
            if (!$this->fallbackFile) {
                throw new \InvalidArgumentException('translations file not exists');
            }
            $name = $this->fallbackFile;
        }

        $content = $this->filesystem->get($name)->getContent();

        return new TranslationsEntry($name, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationsByKey($key)
    {
        if (!$this->filesystem->has($key)) {
            throw new \InvalidArgumentException('translations file not exists');
        }
        $updateDate = new \DateTime();
        $updateDate->setTimestamp($this->filesystem->get($key)->getMtime());
        $content = $this->filesystem->get($key)->getContent();

        return new TranslationsEntry($key, $content, $updateDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTranslationsList()
    {
        $keys = $this->filesystem->keys();
        $translations = [];

        foreach ($keys as $key) {
            if (in_array($key, $this->ignoredFiles)) {
                continue;
            }

            $updateDate = new \DateTime();
            $updateDate->setTimestamp($this->filesystem->get($key)->getMtime());
            $translations[] = new TranslationsEntry($key, null, $updateDate);
        }

        return $translations;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTranslation($key)
    {
        return $this->filesystem->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function save(TranslationsEntry $entry, $key = null, $overwrite = true)
    {
        try {
            if (null !== $key && $key !== $entry->getKey()) {
                $this->filesystem->rename($key, $entry->getKey());
            }
            $this->filesystem->write($entry->getKey(), $entry->getContent(), $overwrite);
        } catch (FileAlreadyExists $e) {
            throw new AlreadyExistException();
        } catch (FileNotFound $e) {
            throw new NotExistException();
        }
    }

    /**
     * @param TranslationsEntry $entry
     *
     * @throws NotExistException
     */
    public function remove(TranslationsEntry $entry)
    {
        try {
            $this->filesystem->delete($entry->getKey());
        } catch (FileNotFound $e) {
            throw new NotExistException();
        }
    }
}
