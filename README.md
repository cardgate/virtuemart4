![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate module for VirtueMart 4.x


## Support

This Extension supports VirtueMart version **4.x** with the Joomla versions **5.x**

## Preparation

The usage of this module requires that you have obtained CardGate credentials.
Please visit [**My CardGate**](https://my.cardgate.com/) and retrieve your Site ID and Hash key, or contact your accountmanager.

## Installation
1. Download the most recent [**cardgate.zip**](https://github.com/cardgate/virtuemart4/releases) file to your desktop.

2. Go to the **admin** section of your webshop and select **Extension manager** from the **Extensions** menu.

3. At the option **Upload Package File** click on the **Browse...** button.  
   Select the **cardgate.zip** file.
   
4. Click on the **Upload File & Install** button.
  
## Configuration

1. Go to the **admin** section of your webshop.

2. Now select **Components, Virtuemart, Payment Methods**.

3. Click on the **New** button. 

4. Select the **Payment Method Information** tabpage.

5. Enter the **name** of the payment method and select the desired **payment method**.

6. Enter the other details on this tabpage and click **Save**.

7. Now select the **Configuration** tabpage.

8. Enter the **site ID** and the **hash key**, which you can find at **Sites** on [**My CardGate**](https://my.cardgate.com/).

9. Enter the **Merchant ID** and the **API key**, which will be provided by your account manager.

10. Enter the other relevant configuration information and click on **Save** and **Close**.

11. Repeat **steps 3 to 10** for each **payment method** you wish to activate.

12. Go to [**My CardGate**](https://my.cardgate.com/), choose **Sites** and select the appropriate site.

13. Go to **Connection to the website** and enter the **Callback URL**, for example:
    **http://mywebshop.com/index.php?option=com_cgp&task=callback**
    (Replace **http://mywebshop.com** with the URL of your webshop)

14. When you are **finished testing** make sure that you switch **all activated payment methods** from **Test Mode** to **Live mode** and save it (**Save**). 
 
## Requirements

No further requirements.
