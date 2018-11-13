<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\uploader;

/**
 * Interface SeekableIterator
 * @package rollun\uploader
 */
interface SeekableIterator extends \SeekableIterator
{
    /**
     * {@inheritdoc}
     */
    public function seek($position);
}
