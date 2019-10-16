<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;

final class FinderService
{
    private const CHAR = '$';

    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /** @var ObjectManager */
    private $objectManager;

    public function __construct(
        RedirectRepositoryInterface $redirectRepository,
        ObjectManager $objectManager
    ) {
        $this->redirectRepository = $redirectRepository;
        $this->objectManager = $objectManager;
    }

    public function search(string $source): ?RedirectInterface
    {
        $allRedirects = $this->redirectRepository->findAll();

        /** @var RedirectInterface $redirect */
        foreach ($allRedirects as $redirect) {
            $redirectSource = $redirect->getSource();

            if (strpos($redirectSource, self::CHAR)) {
                return $this->withChar($redirect, $source);
            }
            if ($source === $redirectSource) {
                return $redirect;
            }
        }

        return null;
    }

    private function withChar(RedirectInterface $redirect, string $source): ?RedirectInterface
    {
        if ($this->isLastPositionAndTheSame($redirect->getSource(), $source)) {
            $redirect->onAccess();
            $this->objectManager->flush();

            return $this->updateLastPosition($redirect, $source);
        }

        return null;
    }

    private function isLastPositionAndTheSame(string $redirectSource, string $source): bool
    {
        $position = substr($redirectSource, strrpos($redirectSource, self::CHAR) + 1);

        if (empty($position)) {
            $beforeLastRedirectSource = strtok($redirectSource, self::CHAR);
            $beforeLastSource = (substr($source, 0, strrpos($source, '/'))) . '/';

            if ($beforeLastRedirectSource === $beforeLastSource) {
                return true;
            }
        }

        return false;
    }

    private function updateLastPosition(RedirectInterface $redirect, string $source): RedirectInterface
    {
        $afterLastChar = substr($source, strrpos($source, '/') + 1);
        $redirect->setDestination($redirect->getDestination() . $afterLastChar);

        return $redirect;
    }
}
