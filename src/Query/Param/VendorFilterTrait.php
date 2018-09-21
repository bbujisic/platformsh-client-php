<?php
/**
 * Created by PhpStorm.
 * User: branislavbujisic
 * Date: 21.09.18
 * Time: 12:03
 */

namespace Platformsh\Client\Query\Param;


trait VendorFilterTrait
{
    /**
     * Restrict the query to a vendor.
     * Vendor name will not be validated and
     *
     * @param string|null $vendorName
     */
    public function setVendor(string $vendorName = null): self
    {
        $this->setFilter('vendor', $vendorName);

        return $this;
    }
}