<?
use WHMCS\View\Menu\Item as MenuItem;

add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar)
{
   if (!is_null($primaryNavbar->getChild('Knowledgebase'))) {
       $primaryNavbar->removeChild('Knowledgebase');
   }

   if (!is_null($primaryNavbar->getChild('Support'))) {
       $primaryNavbar->getChild('Support')
                       ->removeChild('Knowledgebase');
   }
});

add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar)
{
   if (!is_null($primaryNavbar->getChild('Announcements'))) {
       $primaryNavbar->removeChild('Announcements');
   }

   if (!is_null($primaryNavbar->getChild('Support'))) {
       $primaryNavbar->getChild('Support')
                       ->removeChild('Announcements');
   }
});

add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar)
{
   if (!is_null($primaryNavbar->getChild('Network Status'))) {
       $primaryNavbar->removeChild('Network Status');
   }

   if (!is_null($primaryNavbar->getChild('Support'))) {
       $primaryNavbar->getChild('Support')
                       ->removeChild('Network Status');
   }
});

// add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar)
// {
//    if (!is_null($primaryNavbar->getChild('Store'))) {
//        $primaryNavbar->removeChild('Store');
//    }

//    if (!is_null($primaryNavbar->getChild('Support'))) {
//        $primaryNavbar->getChild('Support')
//                        ->removeChild('Network Status');
//    }
// });

add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar)
{
   if (!is_null($primaryNavbar->getChild('Support'))) {
       $primaryNavbar->removeChild('Support');
   }

   if (!is_null($primaryNavbar->getChild('Support'))) {
       $primaryNavbar->getChild('Support')
                       ->removeChild('Support');
   }
});

add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar)
{
   if (!is_null($primaryNavbar->getChild('Open Ticket'))) {
       $primaryNavbar->removeChild('Open Ticket');
   }

   if (!is_null($primaryNavbar->getChild('Support'))) {
       $primaryNavbar->getChild('Support')
                       ->removeChild('Open Ticket');
   }
});