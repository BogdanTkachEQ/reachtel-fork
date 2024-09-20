<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

class DkimKeystoreFactory
{

    public function build(DkimKeystoreTypeEnum $keyStoreType, $groupId = null, $selector = null)
    {
        if ($keyStoreType->is(DkimKeystoreTypeEnum::GROUP())) {
            return new GroupDkimKeystore($groupId, $selector, new DkimKeyFactory());
        } elseif ($keyStoreType->is(DkimKeystoreTypeEnum::SYSTEM())) {
            return new SystemDkimKeystore($selector, new DkimKeyFactory());
        }
    }
}
