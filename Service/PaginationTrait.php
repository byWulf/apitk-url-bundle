<?php

declare(strict_types=1);

namespace Shopping\ApiTKUrlBundle\Service;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Shopping\ApiTKCommonBundle\Exception\MissingDependencyException;
use Shopping\ApiTKHeaderBundle\Service\HeaderInformation;
use Shopping\ApiTKUrlBundle\Annotation as Api;
use Shopping\ApiTKUrlBundle\Exception\PaginationException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Trait PaginationTrait.
 *
 * Pagination specific methods for the ApiService.
 *
 * @package Shopping\ApiTKUrlBundle\Service
 */
trait PaginationTrait
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var HeaderInformation
     */
    private $headerInformation;

    /**
     * @var Api\Pagination
     */
    private $pagination;

    /**
     * @var int|null
     */
    private $paginationOffset;

    /**
     * @var int|null
     */
    private $paginationLimit;

    /**
     * @var int
     */
    private $paginationTotal;

    /**
     * @var RequestService
     */
    private $requestService;

    /**
     * PaginationTrait constructor.
     *
     * @param RequestService $requestService
     */
    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Checks if only allowed sort fields were given in the request. Will be called by the event listener.
     *
     * @param Api\Pagination $pagination
     */
    public function handleIsPaginatable(Api\Pagination $pagination): void
    {
        $this->pagination = $pagination;
    }

    /**
     * Applies the requested pagination to the query builder.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @throws PaginationException
     * @throws NonUniqueResultException
     * @throws MissingDependencyException
     */
    public function applyPaginationToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if (!class_exists(QueryBuilder::class)) {
            throw new MissingDependencyException(
                'You need to install doctrine/orm and doctrine/doctrine-bundle > 2.0 to use ORM-capabilities within ApiTK bundles.'
            );
        }

        if ($this->pagination !== null) {
            $queryBuilder->distinct();

            $totalQueryBuilder = clone $queryBuilder;
            $totalQueryBuilder->select('COUNT(DISTINCT ' . $totalQueryBuilder->getRootAliases()[0] . ')');

            try {
                $this->setPaginationTotal((int) $totalQueryBuilder->getQuery()->getSingleScalarResult());
            } catch (\Exception $e) {
                // f.e. for TableNotFoundExceptions
            }

            $queryBuilder->setMaxResults($this->getPaginationLimit());
            $queryBuilder->setFirstResult($this->getPaginationOffset());
        }
    }

    /**
     * Reads the requested pagination from the query part of the url and stores it for later use.
     *
     * @throws PaginationException
     */
    private function parsePagination(): void
    {
        $request = $this->requestService->getMainRequest($this->requestStack);
        $parameter = $request->query->get('limit', null);

        if ($parameter !== null) {
            if ($this->pagination === null) {
                throw new PaginationException('Limit parameter not available in current request.');
            }

            $parts = explode(',', (string) $parameter);
            if (count($parts) === 1) {
                $this->paginationLimit = (int) $parts[0];
            } elseif (count($parts) === 2) {
                $this->paginationOffset = (int) $parts[0];
                $this->paginationLimit = (int) $parts[1];
            } else {
                throw new PaginationException('Invalid limit parameter. Allowed formats: limit=[limit], limit=[offset],[limit]');
            }
        } elseif ($this->pagination !== null) {
            $this->paginationLimit = $this->pagination->maxEntries;
        }
    }

    /**
     * Gets the pagination offset.
     *
     * @throws PaginationException
     *
     * @return int
     */
    public function getPaginationOffset(): int
    {
        $this->parsePagination();

        return $this->paginationOffset ?? 0;
    }

    /**
     * Gets the pagination limit (max results).
     *
     * @throws PaginationException
     *
     * @return int|null
     */
    public function getPaginationLimit(): ?int
    {
        $this->parsePagination();

        return $this->paginationLimit ?? ($this->pagination !== null ? $this->pagination->maxEntries : null);
    }

    /**
     * Sets the total amount of rows for the given filters/sorts.
     *
     * Only has to be set if applyToQueryBuilder() is not used.
     *
     * @param int $paginationTotal
     */
    public function setPaginationTotal(int $paginationTotal): void
    {
        $this->paginationTotal = $paginationTotal;

        $this->headerInformation->add('pagination-total', $this->paginationTotal);
    }

    /**
     * Returns the total amount of rows for the given filters/sorts.
     *
     * @return int|null
     */
    public function getPaginationTotal(): ?int
    {
        return $this->paginationTotal;
    }
}
