<?php
declare(strict_types=1);

namespace App\Components\GridComponent;

use App\Components\AppComponent;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Localization\SimpleTranslator;

abstract class GridComponent extends AppComponent implements IGridComponent
{
    /**
     * @return Datagrid
     */
    public function createComponentGrid(): Datagrid
    {
        $grid = new Datagrid();

        DataGrid::$iconPrefix = 'fas fa-';

        $grid->setItemsPerPageList([20, 50, 100], true);
        $grid->setTemplateFile(__DIR__ . '/templates/datagrid.latte');
        $grid->setRememberState(false);
        $grid->setRefreshUrl(false);

        $translator = new SimpleTranslator([
            'ublaboo_datagrid.no_item_found_reset' => 'Žádné položky nenalezeny. Filtr můžete vynulovat',
            'ublaboo_datagrid.no_item_found' => 'Žádné položky nenalezeny.',
            'ublaboo_datagrid.here' => 'zde',
            'ublaboo_datagrid.items' => 'Položky',
            'ublaboo_datagrid.all' => 'vše',
            'ublaboo_datagrid.from' => 'z',
            'ublaboo_datagrid.reset_filter' => 'Resetovat filtr',
            'ublaboo_datagrid.group_actions' => 'Hromadné akce',
            'ublaboo_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
            'ublaboo_datagrid.hide_column' => 'Skrýt sloupec',
            'ublaboo_datagrid.action' => '',
            'ublaboo_datagrid.previous' => 'Předchozí',
            'ublaboo_datagrid.next' => 'Další',
            'ublaboo_datagrid.choose' => 'Vyberte',
            'ublaboo_datagrid.execute' => 'Provést',
        ]);

        $grid->setTranslator($translator);

        return $grid;
    }
}