<?php 

require_once('vendor/autoload.php');

$stripe = new \Stripe\StripeClient('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
$customer = $stripe->customers->create([
    'description' => 'example customer',
    'email' => 'email@example.com',
    'payment_method' => 'pm_card_visa',
]);
echo $customer;

?>