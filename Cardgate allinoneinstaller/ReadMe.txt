//-----------------------------------------------------------------
// Card Gate payment module for Joomla virtuemart 3.0+
// Version: 3.0.6
// Author: Richard Schoots, Card Gate Plus B.V.
// Email: support@cardgate.com
// Web: http://www.cardgate.com
//------------------------------------------------------------------

The author of this plugin can NEVER be held responsible for this software.
There is no warranty what so ever. You accept this by using this software.


Changelog
=========
3.0.6 - Removed installer notice
3.0.5 - New payment methods: Afterpay, Klarna, Bitcoin
3.0.4 - Fixed class redeclaration error in the callback process.
3.0.3 - Now also compatible with Joomla 3.x
3.0.2 - Improved code for testmode
      - The status of a completed order is now final.
3.0.1 - Improved installer code
3.0.0 - Initial Release


Update Installation
===================
1. In your administrator back office, select the Extension Manager from the Extensions menu
2. Install the .zip file, for example by upload and install
3. Alternatively, you can upload the unzipped file via ftp and then install it.


Installation
============

1. In your administrator back office, select the extension manager from the Extensions menu
2. Install the .zip file, for example by upload and install
3. Alternatively, you can upload the unzipped file via ftp and then install it
4. Select Virtuemart from the components menu
5. Select Payment Methods from the Shop menu, and click on the New button
6. On the Payment Method Information tab,  Fill in the Payment name, and choose the payment method, eg. CardGatePlus Credit Card
7. Fill in the other details on this tab, and click on Save
8. Switch to the Configuration tab
9. Fill in the Site ID that has been made for you by Card Gate
10. Fill in the Hashkey, which you have made in your merchant back-office at Card Gate
11. Fill in the other relevant configuration details and click on Save & Close
12. Repeat steps 4 to 11 for each Card Gate paymentmethod you wish to use 
13. In your Card Gate Merchant back-office, under Sites, select the appropriate site.
14. Set the Control Url, eg. http://yoursite.com/index.php?option=com_cgp&task=callback
   - (Substitute http://yoursite.com in the above url for the url of your website)
15. Return URL and Return URL failed  need not be filled in for this module.
  
When you are done testing, be sure to switch each payment method from "Test Mode" to "Live Mode".

Files
=====

readme.txt : This readme file
Cardgate allinoneinstaller.zip : The CardGate extension for Joomla VirtueMart.
