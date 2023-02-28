<?php

namespace RAH\SEO;

use ACF_Location;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * SEO Field_Groups
 */
class FieldGroupLocationFiltered extends ACF_Location
{

    public function initialize()
    {
        $this->name = 'rhseo_field_group_location_filtered';
        $this->public = false;
    }

    public function match($rule, $screen, $field_group): bool
    {
        $result = apply_filters('rhseo/field_group_location_match', true, $rule, $screen, $field_group);

        if (!is_bool($result)) {
            throw new \Exception("[rhseo] The result of 'rhseo/render_field_group' must be a boolean");
        }

        return $result;
    }
}
