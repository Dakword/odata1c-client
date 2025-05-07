<?php
declare(strict_types=1);

namespace Dakword\OData1C\Client;

use Closure;
use Dakword\OData1C\Client\Exception\ODataException;
use Dakword\OData1C\Client\Exception\QueryException;

class Query
{
    public string $entitySet;
    public array $functions = [];
    public array $compositeKey = [];
    public array $selects = [];
    public array $orders = [];
    public array $wheres = [];
    public array $expands = [];
    public ?int $skip;
    public ?int $top;
    public bool $inlineCount = false;
    public ?bool $allowedOnly = null;

    private ODataClient $client;
    private QueryBuilder $builder;

    private array $entities = [
        'Справочник' => 'Catalog',
        'Документ' => 'Document',
        'Журнал документов' => 'DocumentJournal',
        'Константа' => 'Constant',
        'План обмена' => 'ExchangePlan',
        'План счетов' => 'ChartOfAccounts',
        'План видов расчета' => 'ChartOfCalculationTypes',
        'План видов характеристик' => 'ChartOfCharacteristicTypes',
        'Регистр сведений' => 'InformationRegister',
        'Регистр накопления' => 'AccumulationRegister',
        'Регистр расчета' => 'CalculationRegister',
        'Регистр бухгалтерии' => 'AccountingRegister',
        'Бизнес-процесс' => 'BusinessProcess',
        'Задача' => 'Task',
    ];

    public function __construct(ODataClient $client, $entities)
    {
        $this->client = $client;
        $this->entitySet = $this->entitySet($entities);
        $this->builder = new QueryBuilder($this);
    }

    /**
     * @param array|mixed $columns
     */
    public function select($columns = '*'): self
    {
        $selects = is_array($columns) ? $columns : func_get_args();

        $subFun = function ($argument): string {
            $items = [];
            foreach ($argument as $key => $item) {
                if (!is_numeric($key)) {
                    foreach (array_map('trim', explode(',', is_array($item) ? implode(',', $item) : $item)) as $name) {
                        $items[] = $key . '/' . $name;
                    }
                } else {
                    $items = array_merge(
                        $items,
                        array_map('trim', explode(',', is_array($item) ? implode(',', $item) : $item))
                    );
                }
            }
            return implode(',', $items);
        };

        foreach ($selects as $key => $argument) {
            if (!is_numeric($key)) {
                foreach (array_map('trim', explode(',', is_array($argument) ? implode(',', $argument) : $argument)) as $item) {
                    $this->selects[] = $key . '/' . $item;
                }
            } else {
                $this->selects = array_merge(
                    $this->selects,
                    array_map('trim', explode(',', is_array($argument) ? $subFun($argument) : $argument))
                );
            }
        }

        return $this;
    }

    private function parseWhere(array $args, $logic = 'and'): void
    {
        if (count($args) == 3) {
            $args[1] = strtr($args[1], [
                '=' => 'eq',
                '<>' => 'ne',
                '!=' => 'ne',
                '>' => 'gt',
                '>=' => 'ge',
                '<' => 'lt',
                '<=' => 'le',
            ]);
            $args[2] = $this->quoteValue($args[2], $args[1] === 'eq');
            $this->wheres[] = [$logic, implode(' ', $args)];
        } elseif (count($args) == 2) {
            $args[1] = $this->quoteValue($args[1], true);
            array_splice($args, 1, 0, ['eq']);
            $this->wheres[] = [$logic, implode(' ', $args)];
        } elseif (count($args) == 1) {
            $this->wheres[] = [$logic, $args[0]];
        } else {
            throw new QueryException('WRONG "WHERE" PARAMS COUNT');
        }
    }

    private function _where($args, string $logic): self
    {
        $firstArg = $args[0];
        if (is_callable($firstArg)) {
            $this->wheres[] = [$logic, '(', '*'];
            $firstArg($this);
            $this->wheres[] = ')';
        } elseif (is_array($firstArg)) {
            foreach ($firstArg as $where) {
                $this->parseWhere(is_array($where) ? $where : [$where], $logic);
            }
        } else {
            $this->parseWhere($args, $logic);
        }
        return $this;
    }

    /**
     * @param Closure|string|array $name
     * @param mixed $operator
     * @param mixed $value
     */
    public function where($name, $operator=null, $value=null): self
    {
        return $this->_where(func_get_args(), 'and');
    }

    /**
     * @param Closure|string|array $name
     * @param mixed $operator
     * @param mixed $value
     */
    public function orWhere($name, $operator=null, $value=null): self
    {
        return $this->_where(func_get_args(), 'or');
    }

    public function whereTrue(string $property): self
    {
        return $this->where($property, 'eq', true);
    }

    public function whereFalse(string $property): self
    {
        return $this->where($property, 'eq', false);
    }

    public function whereNull(string $property): self
    {
        return $this->where($property, null);
    }

    public function whereNotNull(string $property): self
    {
        return $this->where($property, 'ne', null);
    }

    public function whereGUID(string $property, $guid): self
    {
        return $this->where($property, 'eq', "guid'$guid'");
    }

    // ---

    public function page(int $page = 1, int $perPage = 100): self
    {
        $this->skip = ($page - 1) * $perPage;
        $this->top = $perPage;
        return $this;
    }

    public function skip(int $count): self
    {
        $this->skip = $count;
        return $this;
    }

    public function top(int $count): self
    {
        $this->top = $count;
        return $this;
    }

    // ---

    public function addCount(): self
    {
        $this->inlineCount = true;
        return $this;
    }

    public function allowedOnly(bool $flag): self
    {
        $this->allowedOnly = $flag;
        return $this;
    }

    // ---

    private function _orderBy(array $args, string $sort): self
    {
        $orders = [];
        foreach (count($args) == 1 && is_array($args[0]) ? $args[0] : $args as $argument) {
            if (is_array($argument)) {
                $orders[] = $argument;
            } else {
                $orders = array_merge(
                    $orders,
                    array_map('trim', explode(',', $argument))
                );
            }
        }
        foreach ($orders as $item) {
            if (is_string($item)) {
                $this->orders[] = [$item, $sort];
            } elseif (is_array($item)) {
                $this->orders[] = $item;
            }
        }

        return $this;
    }

    public function orderBy(...$args): self
    {
        return $this->_orderBy($args, 'asc');
    }

    public function orderByDesc(...$args): self
    {
        return $this->_orderBy($args, 'desc');
    }

    // ---

    public function expand(...$items): self
    {
        foreach ($items as $argument) {
            $this->expands = array_merge(
                $this->expands,
                array_map('trim', explode(',', is_array($argument) ? implode(',', $argument) : $argument))
            );
        }
        return $this;
    }

    public function call(string|array $name, ?array $params = []): self
    {
        if (is_array($name)) {
            $this->functions = $name;
        } else {
            $this->functions = [$name, $params];
        }
        return $this;
    }

    public function queryString(): string
    {
        return $this->builder->build();
    }

    /**
     * @throws ODataException
     */
    public function get(string|array $guid = null)
    {
        if(is_array($guid)) {
            $this->compositeKey = $guid;
        } elseif (is_string($guid) && !empty($guid)) {
            return $this->getByKey($guid);
        }
        $this->checkEntitySet();
        return $this->client->request('GET', $this->builder->build());
    }

    private function getByKey(string $guid)
    {
        $this->checkEntitySet();
        $this->checkGUID($guid);

        $query = $this->entitySet;
        $query .= "(guid'{$guid}')";

        $select = $this->builder->select();
        if (!empty($select)) {
            $query .= '?' . $select;
        }
        return $this->client->request('GET', $query);
    }

    /**
     * @throws ODataException
     */
    public function getValues()
    {
        return $this->get()->values();
    }

    /**
     * @throws ODataException
     */
    public function getFirst()
    {
        return $this->get()->first();
    }

    public function post(string $guid, bool $operational = false): bool
    {
        $this->checkEntitySet();
        $this->checkGUID($guid);
        $query = "{$this->entitySet}(guid'{$guid}')/Post?PostingModeOperational=" . ($operational ? 'true' : 'false') . ')';

        return $this->responseBoolean($this->client->request('POST', $query));
    }

    public function unPost($guid): bool
    {
        $this->checkEntitySet();
        $this->checkGUID($guid);
        $query = "{$this->entitySet}(guid'{$guid}')/Unpost";

        return $this->responseBoolean($this->client->request('POST', $query));
    }

    // ---

    /**
     * @throws ODataException
     * @throws QueryException
     */
    public function count()
    {
        $this->checkEntitySet();

        $query = $this->entitySet . '/$count';
        $filter = $this->builder->filter();
        if (!empty($filter)) {
            $query .= '?' . $filter;
        }
        return $this->client->request('GET', $query);
    }

    // ---

    public function delete(string $guid = '')
    {
        return $this->responseBoolean($this->client->request('DELETE', $this->builder->build($guid)));
    }

    public function create(array $data)
    {
        return $this->client->request('POST', $this->builder->build(), $data);
    }

    public function update(string $guid, array $data)
    {
        return $this->client->request('PATCH', $this->builder->build($guid), $data);
    }

    public function markDelete(string $guid, bool $default = true)
    {
        return $this->update($guid, [
            'DeletionMark' => $default ? 'true' : 'false'
        ]);
    }

    public function unmarkDelete(string $guid)
    {
        return $this->markDelete($guid, false);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    private function responseBoolean($response)
    {
        if (!$response->isOK()) {
            //$this->client->setError($response->getError());
            return false;
        }
        return true;
    }

    private function entitySet(array $entities): string
    {
        $elements = [];
        foreach ($entities as $item) {
            $elements = array_merge($elements, array_map('trim', explode('_', is_array($item) ? implode('_', $item) : $item)));
        }
        $resource = array_shift($elements);

        if (!isset($this->entities[$resource]) && !in_array($resource, $this->entities)) {
            throw new Exception\QueryException('ENTITY "' . $resource . '" MISSING');
        } elseif (isset($this->entities[$resource])) {
            return $this->entities[$resource] . (count($elements) ? ('_' . implode('_', $elements)) : '');
        } else {
            return $resource . (count($elements) ? ('_' . implode('_', $elements)) : '');
        }
    }

    private function quoteValue(mixed $value, bool $isEq): string
    {
        if ((is_string($value) && preg_match("/^'.*'$/", $value))
            || (is_string($value) && ($this->is_uuid($value)))
            || (is_numeric($value) && !$isEq)
        ) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        return "'$value'";
    }

    private function checkEntitySet()
    {
        if (empty($this->entitySet)) {
            throw new Exception\QueryException('ENTITY_MISSING');
        }
    }

    private function checkGUID($guid)
    {
        if (empty($guid)) {
            throw new Exception\QueryException('GUID_MISSING');
        } elseif (!$this->is_uuid($guid)) {
            throw new Exception\QueryException('GUID_WRONG');
        }
    }

    private function is_uuid($uuid)
    {
        return preg_match('/[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-(0|8|9|a|b)[a-f0-9]{3}\-[a-f0-9]{12}/i', $uuid) == 1;
    }
}
