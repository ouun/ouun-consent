var Ouun=window.Ouun||{};Ouun.Consent={giveConsentButton:document.querySelector(".give-consent"),revokeConsentButton:document.querySelector(".revoke-consent"),cookiePreferences:document.querySelector(".cookie-preferences")?.classList||!1,cookiePrefsButton:document.querySelector(".view-preferences"),cookiePrefsCloseButton:document.querySelector(".close-preferences"),applyCookiePrefs:document.querySelector(".apply-cookie-preferences"),cookieUpdatedMessage:document.querySelector(".consent-updated-message").classList,closeUpdatedMessage:document.getElementById("consent-close-updated-message"),consentBanner:document.getElementById("cookie-consent-banner")},Ouun.Consent.has=function(e){return wp_has_consent(e)},Ouun.Consent.set=function(e,n){wp_set_consent(e,n)},Ouun.Consent.setCookie=function(e,n){consent_api_set_cookie(e,n)},Ouun.Consent.getCookie=function(e){return consent_api_get_cookie(e)},Ouun.Consent.cookieSaved=function(){let e=!1;return ouunConsent.categories.filter((e=>"functional"!==e)).forEach((n=>{Ouun.Consent.has(n)&&(e=!0)})),e},Ouun.Consent.getCategories=function(){let e=ouunConsent.alwaysAllowCategories;return ouunConsent.categories.forEach((n=>{Ouun.Consent.has(n)&&e.push(n)})),e},Ouun.Consent.updateCategories=function(){const e=document.getElementsByClassName("category-input");let n=[],o=[];if(Ouun.Consent.cookiePreferences&&Ouun.Consent.cookiePreferences.contains("show"))for(const t of e)t.checked?n.push(t.value):o.push(t.value);if(this.classList.contains("give-consent")){for(const e of ouunConsent.categories)n.push(e);o=[]}else if(this.classList.contains("revoke-consent"))for(const e of ouunConsent.categories)ouunConsent.alwaysAllowCategories.includes(e)||o.push(e);n.push(...ouunConsent.alwaysAllowCategories),Array.from(new Set(n)),Array.from(new Set(o));for(const e of n)Ouun.Consent.set(e,"allow");for(const e of o)Ouun.Consent.set(e,"deny");Ouun.Consent.cookiePreferences&&Ouun.Consent.cookiePreferences.contains("show")&&(Ouun.Consent.cookiePreferences.remove("show"),Ouun.Consent.giveConsentButton.classList.remove("hide"),Ouun.Consent.revokeConsentButton.classList.remove("hide")),document.querySelector(".consent-banner").classList.add("hide"),Ouun.Consent.preferencesUpdatedMessage()},Ouun.Consent.toggleCookiePrefs=function(){const e=Ouun.Consent.giveConsentButton.classList,n=Ouun.Consent.revokeConsentButton.classList;Ouun.Consent.cookiePreferences.toggle("show"),e.toggle("hide"),n.toggle("hide")},Ouun.Consent.maybeDisplayBanner=function(e=!1){(!Ouun.Consent.cookieSaved()&&ouunConsent.shouldDisplayBanner&&Ouun.Consent.consentBanner||e)&&(Ouun.Consent.consentBanner.style.display="block")},Ouun.Consent.preferencesUpdatedMessage=function(){document.querySelector(".consent-updated-message").classList.toggle("show")},Ouun.Consent.maybeDisplayBanner(),Ouun.Consent.giveConsentButton.addEventListener("click",Ouun.Consent.updateCategories),Ouun.Consent.revokeConsentButton.addEventListener("click",Ouun.Consent.updateCategories),Ouun.Consent.cookiePrefsButton&&(Ouun.Consent.cookiePrefsButton.addEventListener("click",Ouun.Consent.toggleCookiePrefs),Ouun.Consent.applyCookiePrefs.addEventListener("click",Ouun.Consent.updateCategories),Ouun.Consent.cookiePrefsCloseButton.addEventListener("click",Ouun.Consent.toggleCookiePrefs)),Ouun.Consent.closeUpdatedMessage.addEventListener("click",(()=>Ouun.Consent.cookieUpdatedMessage.toggle("show")));