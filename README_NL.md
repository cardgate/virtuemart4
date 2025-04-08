![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate module voor VirtueMart 4.x

[![Build Status](https://travis-ci.org/cardgate/virtuemart3.svg?branch=master)](https://travis-ci.org/cardgate/virtuemart3)

## Support

Deze extensie is geschikt voor VirtueMart versie **4.x** met Joomla versie **5.x**

## Voorbereiding

Voor het gebruik van deze module zijn CardGate gegevens nodig.  
Bezoek hiervoor [**Mijn CardGate**](https://my.cardgate.com/) en haal je gegevens op,  
of neem contact op met je accountmanager.  

## Installatie

1. Download het meest recente [**cardgate.zip**](https://github.com/cardgate/virtuemart3/releases/) bestand op je bureaublad.

2. Ga naar het **admin** gedeelte van je webshop en selecteer **Extensiebeheer** uit het **Extensies** menu.

3. Bij de optie **Upload pakketbestand** klik op de knop **Bladeren...**   
   Selecteer het **cardgate.zip** bestand.
   
4. Klik op de knop **Uploaden & Installeren**.  
  
## Configuratie

1. Log in op het **admin** gedeelte van je webshop.

2. Kies nu **Componenten, Virtuemart, Payment Methods**.

3. Klik op de knop **Nieuw**.

4. Kies het **Payment Method Information** tabblad.

5. Vul de **naam** van de betaalmethode in en kies de gewenste **betaalmethode**.

6. Vul de andere details in op dit tabblad en klik op **Opslaan**.

7. Kies nu het **Configuration** tabblad.

8. Vul de **site ID** en de **hash key** in, deze kun je vinden bij **Sites** op [**Mijn CardGate**](https://my.cardgate.com/).

9. Vul de **Merchant ID** en de **API key** in, deze worden  door uw account manager verstrekt.

10. Vul de andere relevante configuratie informatie in en klik op **Opslaan** en **Sluiten**.

11. Herhaal de **stappen 3 tot en met 10** voor iedere **betaalmethode** die je wenst te activeren.

12. Ga naar [**Mijn CardGate**](https://my.cardgate.com/), kies **Sites** en selecteer de juiste site.

13. Vul bij **Technische Koppeling** de **Callback URL** in, bijvoorbeeld:  
    **http://mijnwebshop.com/index.php?option=com_cgp&task=callback**  
   (Vervang **http://mijnwebshop.com** met de URL van je webshop)  

14. Zorg ervoor dat je na het testen **alle betaalmethoden** omschakelt van **Test Mode** naar **Live mode** en sla het op (**Save**).
 
## Vereisten

Geen verdere vereisten.
