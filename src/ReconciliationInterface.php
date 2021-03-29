<?php


namespace Webgriffe\SyliusAkeneoPlugin;


interface ReconciliationInterface
{
    public function reconcile(array $identifiersToReconcileWith): void;
}
