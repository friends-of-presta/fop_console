<?php
/**
 * 2019-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2019-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace FOP\Console\Commands\Images;

use FOP\Console\Command;

abstract class GenerateAbstract extends Command
{

    /** @var array */
    protected $errors = [];

    /** @var OutputInterface */
    protected $output;

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        return parent::initialize($input, $output);
    }


    /**
     * The whole logic of this file comes from controllers/admin/AdminImagesController.php
     * with some light adaptations
     * @param string $type
     * @param bool $deleteOldImages
     * @param array $formats
     * @return bool
     */
    protected function _regenerateThumbnails($type = 'all', $deleteOldImages = true, $formats = ['all'])
    {
        $languages = Language::getLanguages(false);
        $process = array(
            array('type' => 'categories', 'dir' => _PS_CAT_IMG_DIR_),
            array('type' => 'manufacturers', 'dir' => _PS_MANU_IMG_DIR_),
            array('type' => 'suppliers', 'dir' => _PS_SUPP_IMG_DIR_),
            array('type' => 'products', 'dir' => _PS_PROD_IMG_DIR_),
            array('type' => 'stores', 'dir' => _PS_STORE_IMG_DIR_),
        );

        // Launching generation process
        foreach ($process as $proc) {
            if ($type != 'all' && $type != $proc['type']) {
                continue;
            }

            //Display which type currently processing
            $this->output->writeln('Processing ' . $proc['type']);

            // Getting format generation
            $formats = ImageType::getImagesTypes($proc['type']);
            if ($type != 'all') {

                //@Todo : Manage Format
                $format = (string)(Tools::getValue('format_' . $type));
                if ($format != 'all') {
                    foreach ($formats as $k => $form) {
                        if ($form['id_image_type'] != $format) {
                            unset($formats[$k]);
                        }
                    }
                }
            }

            if ($deleteOldImages) {
                $this->_deleteOldImages($proc['dir'], $formats, ($proc['type'] == 'products' ? true : false));
            }
            if (($return = $this->_regenerateNewImages($proc['dir'], $formats, ($proc['type'] == 'products' ? true : false))) === true) {
                if (!count($this->errors)) {
                    $this->errors[] = sprintf('Cannot write images for this type: %s. Please check the %s folder\'s writing permissions.', $proc['type'], $proc['dir']);
                }
            } else {
                if (!count($this->errors)) {
                    if ($this->_regenerateNoPictureImages($proc['dir'], $formats, $languages)) {
                        $this->errors[] = sprintf('Cannot write "No picture" image to %s images folder. Please check the folder\'s writing permissions.', $proc['type']);
                    }
                }
            }
        }

        return count($this->errors) > 0 ? false : true;
    }

    /**
     * Delete resized image then regenerate new one with updated settings.
     *
     * @param string $dir
     * @param array $type
     * @param bool $product
     *
     * @return bool
     */
    protected function _deleteOldImages($dir, $type, $product = false)
    {
        $this->output->writeln('Deleteting old images for type ' . $type);

        if (!is_dir($dir)) {
            $this->errors[] = 'Unable to delete old images for type ' . $type . ' : ' . $dir . ' does not exists';
            return false;
        }

        $toDel = scandir($dir, SCANDIR_SORT_NONE);

        foreach ($toDel as $d) {
            foreach ($type as $imageType) {
                if (preg_match('/^[0-9]+\-' . ($product ? '[0-9]+\-' : '') . $imageType['name'] . '\.jpg$/', $d)
                    || (count($type) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.jpg$/', $d))
                    || preg_match('/^([[:lower:]]{2})\-default\-' . $imageType['name'] . '\.jpg$/', $d)) {
                    if (file_exists($dir . $d)) {
                        unlink($dir . $d);
                    }
                }
            }
        }

        // delete product images using new filesystem.
        if ($product) {
            $productsImages = Image::getAllImages();
            foreach ($productsImages as $image) {
                $imageObj = new Image($image['id_image']);
                $imageObj->id_product = $image['id_product'];
                if (file_exists($dir . $imageObj->getImgFolder())) {
                    $toDel = scandir($dir . $imageObj->getImgFolder(), SCANDIR_SORT_NONE);
                    foreach ($toDel as $d) {
                        foreach ($type as $imageType) {
                            if (preg_match('/^[0-9]+\-' . $imageType['name'] . '\.jpg$/', $d) || (count($type) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.jpg$/', $d))) {
                                if (file_exists($dir . $imageObj->getImgFolder() . $d)) {
                                    unlink($dir . $imageObj->getImgFolder() . $d);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Regenerate images.
     *
     * @param $dir
     * @param $type
     * @param bool $productsImages
     *
     * @return bool|string
     */
    protected function _regenerateNewImages($dir, $type, $productsImages = false)
    {

        if (!is_dir($dir)) {
            $this->errors[] = 'Unable to regenerate new images for type ' . $type . ' : ' . $dir . ' does not exists';
            return false;
        }

        $generate_hight_dpi_images = (bool)Configuration::get('PS_HIGHT_DPI');

        if (!$productsImages) {
            $formated_medium = ImageType::getFormattedName('medium');
            foreach (scandir($dir, SCANDIR_SORT_NONE) as $image) {
                if (preg_match('/^[0-9]*\.jpg$/', $image)) {
                    foreach ($type as $k => $imageType) {
                        // Customizable writing dir
                        $newDir = $dir;
                        if (!file_exists($newDir)) {
                            continue;
                        }

                        if (($dir == _PS_CAT_IMG_DIR_) && ($imageType['name'] == $formated_medium) && is_file(_PS_CAT_IMG_DIR_ . str_replace('.', '_thumb.', $image))) {
                            $image = str_replace('.', '_thumb.', $image);
                        }

                        if (!file_exists($newDir . substr($image, 0, -4) . '-' . stripslashes($imageType['name']) . '.jpg')) {
                            if (!file_exists($dir . $image) || !filesize($dir . $image)) {
                                $this->errors[] = sprintf('Source file does not exist or is empty (%s)', $dir . $image);
                            } elseif (!ImageManager::resize($dir . $image, $newDir . substr(str_replace('_thumb.', '.', $image), 0, -4) . '-' . stripslashes($imageType['name']) . '.jpg', (int)$imageType['width'], (int)$imageType['height'])) {
                                $this->errors[] = sprintf('Failed to resize image file (%s)', $dir . $image);
                            }

                            if ($generate_hight_dpi_images) {
                                if (!ImageManager::resize($dir . $image, $newDir . substr($image, 0, -4) . '-' . stripslashes($imageType['name']) . '2x.jpg', (int)$imageType['width'] * 2, (int)$imageType['height'] * 2)) {
                                    $this->errors[] = sprintf('Failed to resize image file to high resolution %s', $dir . $image);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            foreach (Image::getAllImages() as $image) {
                $imageObj = new Image($image['id_image']);
                $existing_img = $dir . $imageObj->getExistingImgPath() . '.jpg';
                if (file_exists($existing_img) && filesize($existing_img)) {
                    foreach ($type as $imageType) {
                        if (!file_exists($dir . $imageObj->getExistingImgPath() . '-' . stripslashes($imageType['name']) . '.jpg')) {
                            if (!ImageManager::resize($existing_img, $dir . $imageObj->getExistingImgPath() . '-' . stripslashes($imageType['name']) . '.jpg', (int)$imageType['width'], (int)$imageType['height'])) {
                                $this->errors[] = sprintf(
                                    'Original image is corrupt %s for product ID %s or bad permission on folder.',
                                    $existing_img,
                                    (int)$imageObj->id_product
                                );
                            }

                            if ($generate_hight_dpi_images) {
                                if (!ImageManager::resize($existing_img, $dir . $imageObj->getExistingImgPath() . '-' . stripslashes($imageType['name']) . '2x.jpg', (int)$imageType['width'] * 2, (int)$imageType['height'] * 2)) {
                                    $this->errors[] = sprintf(
                                        'Original image is corrupt %s for product ID %s or bad permission on folder.',
                                        $existing_img,
                                        (int)$imageObj->id_product
                                    );
                                }
                            }
                        }
                    }
                } else {
                    $this->errors[] = sprintf(
                        'Original image is missing or empty %s for product ID %s',
                        $existing_img,
                        (int)$imageObj->id_product
                    );
                }
            }
        }

        return (bool)count($this->errors);
    }

    /**
     * Regenerate no-pictures images.
     *
     * @param $dir
     * @param $type
     * @param $languages
     *
     * @return bool
     */
    protected function _regenerateNoPictureImages($dir, $type, $languages)
    {
        $errors = false;
        $generate_hight_dpi_images = (bool)Configuration::get('PS_HIGHT_DPI');

        foreach ($type as $image_type) {
            foreach ($languages as $language) {
                $file = $dir . $language['iso_code'] . '.jpg';
                if (!file_exists($file)) {
                    $file = _PS_PROD_IMG_DIR_ . Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT')) . '.jpg';
                }
                if (!file_exists($dir . $language['iso_code'] . '-default-' . stripslashes($image_type['name']) . '.jpg')) {
                    if (!ImageManager::resize($file, $dir . $language['iso_code'] . '-default-' . stripslashes($image_type['name']) . '.jpg', (int)$image_type['width'], (int)$image_type['height'])) {
                        $errors = true;
                    }

                    if ($generate_hight_dpi_images) {
                        if (!ImageManager::resize($file, $dir . $language['iso_code'] . '-default-' . stripslashes($image_type['name']) . '2x.jpg', (int)$image_type['width'] * 2, (int)$image_type['height'] * 2)) {
                            $errors = true;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /* Hook watermark optimization */
    protected function _regenerateWatermark($dir, $type = null)
    {
        $result = Db::getInstance()->executeS('
		SELECT m.`name` FROM `' . _DB_PREFIX_ . 'module` m
		LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_module` = m.`id_module`
		LEFT JOIN `' . _DB_PREFIX_ . 'hook` h ON hm.`id_hook` = h.`id_hook`
		WHERE h.`name` = \'actionWatermark\' AND m.`active` = 1');

        if ($result && count($result)) {
            $productsImages = Image::getAllImages();
            foreach ($productsImages as $image) {
                $imageObj = new Image($image['id_image']);
                if (file_exists($dir . $imageObj->getExistingImgPath() . '.jpg')) {
                    foreach ($result as $module) {
                        $moduleInstance = Module::getInstanceByName($module['name']);
                        if ($moduleInstance && is_callable(array($moduleInstance, 'hookActionWatermark'))) {
                            call_user_func(array($moduleInstance, 'hookActionWatermark'), array('id_image' => $imageObj->id, 'id_product' => $imageObj->id_product, 'image_type' => $type));
                        }
                    }
                }
            }
        }
    }

}