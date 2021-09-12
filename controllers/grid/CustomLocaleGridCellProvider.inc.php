<?php

/**
 * @file controllers/grid/CustomLocaleGridCellProvider.inc.php
 *
 * Copyright (c) 2016-2021 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleGridCellProvider
 */

use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridHandler;
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
        $customLocale = $row->getData();
        if ($column->getId() === 'filepath') {
            $router = $request->getRouter();
            return [new LinkAction(
                'edit',
                new AjaxModal(
                    $router->url($request, null, null, 'editLocaleFile', null, ['locale' => $customLocale->getLocale(), 'filePath' => $customLocale->getFilePath()]),
                    __('grid.action.edit'),
                    'modal_edit',
                    true
                ),
                __('common.edit'),
                null
            )];
        }
        return parent::getCellActions($request, $row, $column, $position);
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

        switch ($column->getId()) {
            case 'filepath':
                // The action has the label
                return ['label' => ''];
            case 'filetitle':
                return ['label' => $customLocale->getFilePath()];
        }
        return parent::getTemplateVarsFromRowColumn($row, $column);
    }
}
