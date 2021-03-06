<?php

namespace Alchemy\core;


/**
 * Interface for SQL elements
 */
interface IElement {

    /**
     * Apply a tag to this element. The same tag cannot be applied
     * with two different values.
     *
     * @param string $tag
     * @param string $value optional value to give tag
     */
    public function addTag($tag, $value = true);


    /**
     * Get the locally-unique element id
     *
     * @return string
     */
    public function getID();


    /**
     * If the tag has been applied to this object, returns its
     * value, else false
     *
     * @param  string $tag tag name
     * @return mixed       value or false
     */
    public function getTag($tag);


    /**
     * List of all tags that apply to this element
     *
     * @return array
     */
    public function listTags();
}