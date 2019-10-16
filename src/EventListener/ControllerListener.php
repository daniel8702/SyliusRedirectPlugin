<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Service\FinderService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

final class ControllerListener
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;
    /** @var FinderService */
    private $finderService;

    /**
     * @param RedirectRepositoryInterface $redirectRepository
     * @param FinderService $finderService
     */
    public function __construct(
        RedirectRepositoryInterface $redirectRepository,
        FinderService $finderService
    ) {
        $this->redirectRepository = $redirectRepository;
        $this->finderService = $finderService;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        $redirect = $this->finderService->search($pathInfo);

        if ($redirect instanceof RedirectInterface) {
            $lastRedirect = $this->redirectRepository->findLastRedirect($redirect, false);

            $event->setController(function () use ($lastRedirect): RedirectResponse {
                return new RedirectResponse($lastRedirect->getDestination(), $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
            });
        }
    }
}
