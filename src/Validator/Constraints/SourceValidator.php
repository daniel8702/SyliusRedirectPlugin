<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SourceValidator extends ConstraintValidator
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /**
     * SourceValidator constructor.
     *
     * @param RedirectRepositoryInterface $redirectRepository
     */
    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param mixed             $value
     * @param Constraint|Source $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Source) {
            return;
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        /** @var RedirectInterface|null $redirect */
        $redirect = $this->context->getObject();
        if (!$redirect instanceof RedirectInterface) {
            return;
        }
    
        if (!$redirect->isEnabled()) {
            return;
        }

        /** @var array|RedirectInterface[] $conflictingRedirects */
        $conflictingRedirects = $this->redirectRepository->findBy(['source' => $value, 'enabled' => true]);
        if ($redirect !== null) {
            $conflictingRedirects = array_filter($conflictingRedirects, function (RedirectInterface $conflictingRedirect) use ($redirect): bool {
                return $conflictingRedirect->getId() !== $redirect->getId();
            });
        }
        if (!empty($conflictingRedirects)) {
            $conflictingIds = implode(
                ', ',
                array_map(function (RedirectInterface $item) {
                    return $item->getId();
                }, $conflictingRedirects)
            );
            $this->context->buildViolation($constraint->message)
                ->atPath('source')
                ->setParameter('{{ source }}', $value)
                ->setParameter('{{ conflictingIds }}', $conflictingIds)
                ->addViolation();
        }
    }
}