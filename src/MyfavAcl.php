<?php declare(strict_types=1);

namespace Myfav\Acl;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class MyfavAcl extends Plugin {
    /**
     * install
     *
     * @param  InstallContext $installContext
     * @return void
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    /**
     * uninstall
     *
     * @param  UninstallContext $context
     * @return void
     */
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }
    }

    /**
     * update
     *
     * @param  UpdateContext $updateContext
     * @return void
     */
    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
    }
}