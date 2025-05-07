<?php

namespace Dakword\OData1C\Client\Tests;

use Dakword\OData1C\Client\Exception\QueryException;
use Dakword\OData1C\Client\ODataClient;
use Dakword\OData1C\Client\Query;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class QueryTest extends PHPUnitTestCase
{
    private $baseUri = 'http://localhost/DemoARAutomation25/odata/standard.odata';
    private $auth = [
        'login' => 'Administrator',
        'password' => '',
    ];
    protected $client;

    public function setUp(): void
    {
        $this->client = new ODataClient($this->baseUri, $this->auth['login'], $this->auth['password']);
    }

    public function testQuery()
    {
        self::assertEquals(
            'Catalog_Номенклатура',
            $this->client->query('Catalog_Номенклатура')->queryString()
        );
        self::assertEquals(
            'Catalog_Номенклатура',
            $this->client->query('Справочник_Номенклатура')->queryString()
        );
        self::assertEquals(
            'Catalog_Склады_КонтактнаяИнформация',
            $this->client->query('Справочник', 'Склады_КонтактнаяИнформация')->queryString()
        );
        self::assertEquals(
            'InformationRegister_ЦеныНоменклатуры_RecordType',
            $this->client->query(['Регистр сведений', 'ЦеныНоменклатуры', 'RecordType'])->queryString()
        );

        $this->assertInstanceOf(Query::class, $this->client->query('Справочник_Номенклатура'));
    }

    public function testQueryException()
    {
        self::expectException(QueryException::class);
        self::assertInstanceOf(Query::class, $this->client->query('Секс'));
    }

    public function testCounts()
    {
        self::assertEquals(
            'Document_ЗаказКлиента?$inlinecount=allpages',
            $this->client->query('Документ_ЗаказКлиента')
                ->addCount()
            ->queryString()
        );
    }

    public function testSelect()
    {
        $expected = 'Catalog_Номенклатура?$select=Ref_Key,Description,IsFolder';

        $query1 = $this->client->query('Справочник_Номенклатура')
            ->select('Ref_Key')
            ->select('Description')
            ->select('IsFolder')
        ->queryString();
        self::assertEquals($expected, $query1);

        $query2 = $this->client->query('Справочник_Номенклатура')
            ->select('Ref_Key', 'Description, IsFolder')
        ->queryString();
        self::assertEquals($expected, $query2);

        $query3 = $this->client->query('Справочник_Номенклатура')
            ->select('Ref_Key, Description, IsFolder')
        ->queryString();
        self::assertEquals($expected, $query3);

        $query4 = $this->client->query('Справочник_Номенклатура')
            ->select(['Ref_Key', 'Description, IsFolder'])
        ->queryString();
        self::assertEquals($expected, $query4);

        self::assertEquals(
            'Document_ЗаказКлиента?$select=*,Товары/Номенклатура_Key,Товары/Характеристика_Key,Товары/Количество',
            $this->client->query('Документ_ЗаказКлиента')
                ->select(
                    '*',
                    ['Товары' => 'Номенклатура_Key, Характеристика_Key, Количество'],
                )
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$select=*,Товары/Номенклатура_Key,Товары/Характеристика_Key,Товары/Количество',
            $this->client->query('Документ_ЗаказКлиента')
                ->select([
                    '*',
                    ['Товары' => 'Номенклатура_Key, Характеристика_Key, Количество'],
                ])
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$select=*,Товары/Номенклатура_Key,Товары/Характеристика_Key,Товары/Количество',
            $this->client->query('Документ_ЗаказКлиента')
                ->select([
                    '*',
                    'Товары' => 'Номенклатура_Key, Характеристика_Key, Количество',
                ])
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$select=*,Товары/Номенклатура_Key,Товары/Характеристика_Key,Товары/Количество',
            $this->client->query('Документ_ЗаказКлиента')
                ->select([
                    '*',
                    'Товары' => ['Номенклатура_Key, Характеристика_Key, Количество'],
                ])
            ->queryString()
        );
    }

    public function testExpand()
    {
        $expected = 'Document_ЗаказКлиента?$expand=Организация,Контрагент/*,Склад';

        self::assertEquals(
            $expected,
            $this->client->query('Документ_ЗаказКлиента')
                ->expand('Организация', 'Контрагент/*, Склад')
            ->queryString()
        );
        self::assertEquals(
            $expected,
            $this->client->query('Документ_ЗаказКлиента')
                ->expand('Организация, Контрагент/*, Склад')
            ->queryString()
        );
        self::assertEquals(
            $expected,
            $this->client->query('Документ_ЗаказКлиента')
                ->expand(['Организация', 'Контрагент/*', 'Склад'])
            ->queryString()
        );
    }

    public function testOrderBy()
    {
        self::assertEquals(
            'Document_ЗаказКлиента?$orderby=IsFolder asc,Description asc',
            $this->client->query('Документ_ЗаказКлиента')
                ->orderBy('IsFolder', 'Description')
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$orderby=IsFolder asc,Description asc',
            $this->client->query('Документ_ЗаказКлиента')
                ->orderBy('IsFolder, Description')
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$orderby=IsFolder asc,Description asc',
            $this->client->query('Документ_ЗаказКлиента')
                ->orderBy(['IsFolder, Description'])
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$orderby=IsFolder asc,Description asc',
            $this->client->query('Документ_ЗаказКлиента')
                ->orderBy(['IsFolder', 'Description'])
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$orderby=IsFolder asc,Description desc',
            $this->client->query('Документ_ЗаказКлиента')
                ->orderBy([
                    'IsFolder',
                    ['Description', 'desc'],
                ])
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$orderby=IsFolder asc,Description desc',
            $this->client->query('Документ_ЗаказКлиента')
                ->orderBy(
                    'IsFolder',
                    ['Description', 'desc'],
                )
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$orderby=Контрагент/ИНН asc,Date desc',
            $this->client->query('Документ_ЗаказКлиента')
                ->orderBy('Контрагент/ИНН')
                ->orderByDesc('Date')
            ->queryString()
        );
    }

    public function testPaginate()
    {
        self::assertEquals(
            'Document_ЗаказКлиента?$skip=100&$top=100',
            $this->client->query('Документ_ЗаказКлиента')
                ->skip(100)
                ->top(100)
            ->queryString()
        );
        self::assertEquals(
            'Document_ЗаказКлиента?$skip=100&$top=100',
            $this->client->query('Документ_ЗаказКлиента')
                ->page(2)
            ->queryString()
        );
    }

    public function testAllowedOnly()
    {
        self::assertEquals(
            'Document_ЗаказКлиента?$allowedOnly=true',
            $this->client->query('Документ_ЗаказКлиента')
                ->allowedOnly(true)
            ->queryString()
        );
    }

    public function testCall()
    {
        self::assertEquals(
            "InformationRegister_КурсыВалют/SliceFirst(Period=datetime'2024-09-01T00:00:00',Condition=Валюта_Key eq guid'value')",
            $this->client->query('Регистр сведений', 'КурсыВалют')
                ->call('SliceFirst', [
                    'Period' => "datetime'2024-09-01T00:00:00'",
                    'Condition'=> "Валюта_Key eq guid'value'",
                ])
            ->queryString()
        );
        self::assertEquals(
            "InformationRegister_КурсыВалют/SliceFirst()",
            $this->client->query('Регистр сведений', 'КурсыВалют')
                ->call('SliceFirst')
            ->queryString()
        );
    }

    public function testWhere()
    {
        self::assertEquals(
            "Document_ЗаказКлиента?\$filter=Имя eq 'Молоко' and Цена lt 250",
            $this->client->query('Документ_ЗаказКлиента')
                ->where("Имя eq 'Молоко' and Цена lt 250")
            ->queryString()
        );
        self::assertEquals(
            "Document_ЗаказКлиента?\$filter=IsFolder eq true and IsFolder eq true and IsFolder = true",
            $this->client->query('Документ_ЗаказКлиента')
                ->where([
                    ['IsFolder', '=', true],
                    ['IsFolder', true],
                    'IsFolder = true',
                ])
            ->queryString()
        );
        self::assertEquals(
            "Document_ЗаказКлиента?\$filter=EQ eq 'XXL' and GT gt 20 and EQ eq '20' and EQ eq 'eq' and NE ne 'ne' and NE ne 'ne' and GT gt 'gt' and LT lt 'lt' and GE ge 'ge' and LE le 'le'",
            $this->client->query('Документ_ЗаказКлиента')
                ->where('EQ', 'XXL')
                ->where('GT', 'gt', 20)
                ->where('EQ', '=', '20')
                ->where('EQ', '=', 'eq')
                ->where('NE', '<>', 'ne')
                ->where('NE', '!=', 'ne')
                ->where('GT', '>', 'gt')
                ->where('LT', '<', 'lt')
                ->where('GE', '>=', 'ge')
                ->where('LE', '<=', 'le')
            ->queryString()
        );
        self::assertEquals(
            "Document_ЗаказКлиента?\$filter=(ФормаОплаты eq 'ПлатежнаяКарта' or ФормаОплаты eq 'Безналичная') and СуммаДокумента gt 2000",
            $this->client->query('Документ_ЗаказКлиента')
                ->where(fn($query) => $query
                    ->where('ФормаОплаты', 'ПлатежнаяКарта')
                    ->orWhere('ФормаОплаты', 'Безналичная')
                )
                ->where('СуммаДокумента', 'gt', 2000)
            ->queryString()
        );
        self::assertEquals(
            "Document_ЗаказКлиента?\$filter=((X1 eq 'X1' or X2 eq 'X2') or (X3 eq 'X3' and X4)) and X5 gt 2000",
            $this->client->query('Документ_ЗаказКлиента')
                ->where(fn($query) => $query
                    ->where(fn($query) => $query
                        ->where('X1', 'X1')
                        ->orWhere('X2', 'X2')
                    )
                    ->orWhere(fn($query) => $query
                        ->where('X3', 'X3')
                        ->where('X4')
                    )
                )
                ->where('X5', 'gt', 2000)
            ->queryString()
        );
    }
}
