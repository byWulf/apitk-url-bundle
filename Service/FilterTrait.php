<?php

declare(strict_types=1);

namespace Shopping\ApiTKUrlBundle\Service;

use Doctrine\ORM\QueryBuilder;
use Shopping\ApiTKCommonBundle\Exception\MissingDependencyException;
use Shopping\ApiTKUrlBundle\Annotation as Api;
use Shopping\ApiTKUrlBundle\Exception\FilterException;
use Shopping\ApiTKUrlBundle\Input\FilterField;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Trait FilterTrait.
 *
 * Filter specific methods for the ApiService.
 *
 * @package Shopping\ApiTKUrlBundle\Service
 */
trait FilterTrait
{
    /**
     * @var FilterField[]
     */
    private $filterFields;

    /**
     * @var Api\Filter[]
     */
    private $filters = [];

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RequestService
     */
    private $requestService;

    /**
     * FilterTrait constructor.
     *
     * @param RequestService $requestService
     */
    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Checks if only allowed filter fields were given in the request. Will be called by the event listener.
     *
     * @param Api\Filter[] $filters
     *
     * @throws FilterException
     */
    public function handleAllowedFilters(array $filters): void
    {
        $this->filters = $filters;

        foreach ($this->getFilteredFields() as $filterField) {
            if (!$this->isAllowedFilterField($filterField)) {
                throw new FilterException(
                    sprintf(
                        'Filter "%s" with comparison "%s" and value "%s" is not allowed in this request. Available filters: %s',
                        $filterField->getName(),
                        $filterField->getComparison(),
                        is_array($filterField->getValue()) ? implode(',', $filterField->getValue()) : $filterField->getValue(),
                        implode(', ', array_map(function (Api\Filter $filter) {
                            $hints = [];
                            $hints[] = 'comparisons: ' . implode(', ', $filter->allowedComparisons);
                            if (count($filter->enum) > 0) {
                                $hints[] = 'values: ' . implode(', ', $filter->enum);
                            }

                            return $filter->name . ' (' . implode(' // ', $hints) . ')';
                        }, $filters))
                    )
                );
            }
        }
    }

    /**
     * Validates a requested filter field against the annotated allowed filters.
     *
     * @param FilterField $filterField
     *
     * @return bool
     */
    private function isAllowedFilterField(FilterField $filterField): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->name !== $filterField->getName()) {
                continue;
            }

            if (!$this->checkComparison($filterField, $filter)) {
                return false;
            }

            if (!$this->checkEnum($filterField, $filter)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Validates if a given filter's comparison from the request is allowed by its definition.
     *
     * @param FilterField $filterFieldDefinition
     * @param Api\Filter  $filter
     *
     * @return bool
     */
    private function checkComparison(FilterField $filterFieldDefinition, $filter): bool
    {
        return in_array($filterFieldDefinition->getComparison(), $filter->allowedComparisons);
    }

    /**
     * Validates if a given filter value from the request is allowed by its definition.
     * Also checks whether the definition includes an enum and, if so, validates the supplied
     * value against all possible ones.
     *
     * @param FilterField $filterFieldDefinition
     * @param Api\Filter  $filter
     *
     * @return bool
     */
    private function checkEnum(FilterField $filterFieldDefinition, $filter): bool
    {
        if (count($filter->enum) < 1) {
            // no enum criteria for this filter, comparison allowed; everything is fine
            return true;
        }

        $filterValue = $filterFieldDefinition->getValue();
        if (is_array($filterValue)) {
            // supplied filter has multiple values; check if all of them are allowed
            foreach ($filterValue as $value) {
                if (!in_array($value, $filter->enum)) {
                    // exit on first invalid value
                    return false;
                }
            }

            // arriving here, the loop above encountered valid values only
            return true;
        }

        // supplied filter has only one value; check if it's an allowed value
        return in_array($filterValue, $filter->enum);
    }

    /**
     * Returns the annotated filter by filter name.
     *
     * @param string $name
     *
     * @return Api\Filter|null
     */
    private function getFilterByName(string $name): ?Api\Filter
    {
        foreach ($this->filters as $filter) {
            if ($filter->name === $name) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * Reads the requested filter fields by the query party of the url.
     */
    private function loadFiltersFromQuery(): void
    {
        $request = $this->requestService->getMainRequest($this->requestStack);

        if (Kernel::VERSION_ID >= 50100) {
            $requestFilters = $request->query->all('filter');
        } else {
            $requestFilters = $request->query->get('filter');
        }

        if (is_array($requestFilters)) {
            foreach ($requestFilters as $name => $limitations) {
                foreach ($limitations as $comparison => $value) {
                    $filterField = new FilterField();
                    $filterField->setName($name)
                        ->setValue($value)
                        ->setComparison($comparison)
                        ->setFilter($this->getFilterByName($name));

                    $this->filterFields[] = $filterField;
                }
            }
        }
    }

    /**
     * Reads the requested filter fields by the placeholder parts of the route.
     */
    private function loadFiltersFromAttributes(): void
    {
        $request = $this->requestService->getMainRequest($this->requestStack);
        foreach ($request->attributes->getIterator() as $key => $value) {
            $key = (string) $key;
            if ($this->getFilterByName($key) === null) {
                continue;
            }

            $filterField = new FilterField();
            $filterField->setName($key)
                ->setValue($value)
                ->setComparison(Api\Filter::COMPARISON_EQUALS)
                ->setFilter($this->getFilterByName($key));

            $this->filterFields[] = $filterField;
        }
    }

    /**
     * Returns all requested filter fields from the client.
     *
     * @return FilterField[]
     */
    public function getFilteredFields(): array
    {
        if ($this->filterFields === null) {
            $this->filterFields = [];
            $this->loadFiltersFromQuery();
            $this->loadFiltersFromAttributes();
        }

        return $this->filterFields;
    }

    /**
     * Returns true if this filtering for this filter was requested by the user.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasFilteredField(string $name): bool
    {
        return $this->getFilteredField($name) !== null;
    }

    /**
     * Returns the filter field for the given name.
     *
     * @param string      $name
     * @param string|null $comparison
     *
     * @return FilterField|null
     */
    public function getFilteredField(string $name, string $comparison = null): ?FilterField
    {
        foreach ($this->getFilteredFields() as $filterField) {
            if (
                $filterField->getName() === $name &&
                (
                    $comparison === null ||
                    $filterField->getComparison() === $comparison
                )
            ) {
                return $filterField;
            }
        }

        return null;
    }

    /**
     * Applies all requested filter fields to the query builder.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @throws MissingDependencyException
     */
    public function applyFilteredFieldsToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if (!class_exists(QueryBuilder::class)) {
            throw new MissingDependencyException(
                'You need to install doctrine/orm and doctrine/doctrine-bundle > 2.0 to use ORM-capabilities within ApiTK bundles.'
            );
        }

        foreach ($this->getFilteredFields() as $filterField) {
            $filterField->applyToQueryBuilder($queryBuilder);
        }
    }
}
