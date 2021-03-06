<?php

namespace spec\Pim\Bundle\VersioningBundle\EventSubscriber;

use Oro\Bundle\UserBundle\Entity\User;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\VersioningBundle\Event\BuildVersionEvent;
use Pim\Bundle\VersioningBundle\Event\BuildVersionEvents;
use Prophecy\Argument;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AddUserSubscriberSpec extends ObjectBehavior
{
    function let(SecurityContextInterface $security, TokenInterface $token)
    {
        $this->beConstructedWith($security);

        $security->isGranted(Argument::any())->willReturn(true);
    }

    function it_is_an_event_listener()
    {
        $this->shouldImplement('Symfony\Component\EventDispatcher\EventSubscriberInterface');
    }

    function it_subscribes_to_the_kernel_request_event()
    {
        $this->getSubscribedEvents()->shouldReturn([BuildVersionEvents::PRE_BUILD => 'preBuild']);
    }

    function it_injects_current_username_into_the_version_manager(BuildVersionEvent $event, $security, $token, User $user)
    {
        $security->getToken()->willReturn($token);
        $token->getUser()->willReturn($user);
        $user->getUsername()->willReturn('foo');

        $this->preBuild($event);
    }

    function it_does_nothing_if_a_token_is_not_present_in_the_security_context(BuildVersionEvent $event, $security)
    {
        $security->getToken()->willReturn(null);

        $this->preBuild($event);
    }
}
