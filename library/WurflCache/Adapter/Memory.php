<?php
namespace WurflCache\Adapter;

/**
 * a outsourced cache class
 *
 * PHP version 5
 *
 * Copyright (c) 2013 Thomas M�ller
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    Browscap
 * @author     Thomas M�ller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2013 Thomas M�ller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/phpbrowscap/
 */
class Memory implements AdapterInterface
{
    /**
     * @var array
     */
    private $map = array();

    /**
     * loads the content from the cache
     *
     * @param string  $cacheId  The cache id
     * @param boolean &$success A flag to tell if the cache was loaded
     *
     * @return mixed The content that was saved before
     */
    public function getItem($cacheId, &$success)
    {
        if (isset($this->map[$cacheId])) {
            $success = true;
            return $this->map[$cacheId];
        }
        
        $success = false;
        return null;
    }

    /**
     * save the content into the zend cache
     *
     * @param string $cacheId The cache id
     * @param mixed  $content The content to store
     *
     * @return boolean whether the content was stored
     */
    public function setItem($cacheId, $content)
    {
        $this->map[$cacheId] = $content;
        
        return true;
    }

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     *
     * @return bool
     */
    public function hasItem($cacheId)
    {
        return isset($this->map[$cacheId]);
    }
}