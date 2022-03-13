<?php

/**
 * @file controllers/grid/CustomLocaleGridCellProvider.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleGridCellProvider
 */

namespace APP\plugins\generic\customLocale\controllers\grid;

use APP\plugins\generic\customLocale\classes\CustomLocale;
use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

class CustomLocaleGridCellProvider extends GridCellProvider
{
    /**
     * Get cell actions associated with this row/column combination
     *
     * @param PKPRequest $request
     * @param GridRow $row
     * @param GridColumn $column
     * @param int $position GRID_ACTION_POSITION_...
     *
     * @return array an array of LinkAction instances
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT): array
    {
        /** @var CustomLocale */
        $customLocale = $row->getData();
        return match ($column->getId()) {
            'action' => [new LinkAction(
                'edit',
                new AjaxModal(
                    $request->getRouter()->url($request, null, null, 'editLocale', null, ['locale' => $customLocale->getLocale()]),
                    __('grid.action.edit'),
                    'modal_edit',
                    true
                ),
                __('common.edit'),
                null
            )],
            default => parent::getCellActions($request, $row, $column, $position)
        };
    }

    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @param GridRow $row
     * @param GridColumn $column
     */
    public function getTemplateVarsFromRowColumn($row, $column): array
    {
        /** @var CustomLocale */
        $customLocale = $row->getData();

        return match ($column->getId()) {
            'locale' => ['label' => $customLocale->getLocale()],
            'name' => ['label' => $customLocale->getName()],
            'action' => ['label' => ''],
            default => parent::getTemplateVarsFromRowColumn($row, $column)
        };
    }
}
