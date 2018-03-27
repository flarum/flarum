<?php

namespace Studio\Parts\License;

use Composer\Spdx\SpdxLicenses;
use Studio\Filesystem\Directory;
use Studio\Parts\AbstractPart;

class Part extends AbstractPart
{
    public function setupPackage($composer, Directory $target)
    {
        if ($this->input->confirm('Do you want to configure a license for your project?')) {
            $licenses = new SpdxLicenses();

            $license = $this->selectLicenseFromList($licenses);

            $this->copyLicenseFileTo($target, $license);

            $composer->license = $license;
        }
    }

    protected function selectLicenseFromList($licenses)
    {
        // Ask the user to chosse a license from the list
    }

    protected function copyLicenseFileTo(Directory $target, $license)
    {
        // Download the file
        // Add year and name
    }
}
