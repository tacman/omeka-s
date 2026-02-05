<?php

namespace App\Twig\Component\Admin;

use App\Model\BrowseResult;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AdminBrowseTable', template: 'components/admin/BrowseTable.html.twig')]
final class BrowseTable
{
    public BrowseResult $result;
}
