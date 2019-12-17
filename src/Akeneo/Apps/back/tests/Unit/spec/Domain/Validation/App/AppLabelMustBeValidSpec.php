<?php
declare(strict_types=1);

namespace spec\Akeneo\Apps\Domain\Validation\App;

use Akeneo\Apps\Domain\Validation\App\AppLabelMustBeValid;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AppLabelMustBeValidSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(AppLabelMustBeValid::class);
    }

    public function it_does_not_build_violation_on_valid_label(ExecutionContextInterface $context)
    {
        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->validate('Sylius Connector', $context);
    }

    public function it_adds_a_violation_when_the_label_is_invalid(
        ExecutionContextInterface $context,
        ConstraintViolationBuilderInterface $builder
    ) {
        $context->buildViolation('akeneo_apps.app.constraint.label.too_long')->willReturn($builder);
        $builder->addViolation()->shouldBeCalled();

        $this->validate(str_repeat('A', 103), $context);
    }

    public function it_adds_a_violation_when_the_label_is_empty(
        ExecutionContextInterface $context,
        ConstraintViolationBuilderInterface $builder
    ) {
        $context->buildViolation('akeneo_apps.app.constraint.label.required')->willReturn($builder);
        $builder->addViolation()->shouldBeCalled();

        $this->validate('', $context);
    }
}