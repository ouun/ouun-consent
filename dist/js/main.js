var Altis=window.Altis||{};Altis.Consent={giveConsentButton:document.querySelector(".give-consent"),revokeConsentButton:document.querySelector(".revoke-consent"),cookiePreferences:document.querySelector(".cookie-preferences").classList,cookiePrefsButton:document.querySelector(".view-preferences"),applyCookiePrefs:document.querySelector(".apply-cookie-preferences"),cookieUpdatedMessage:document.querySelector(".consent-updated-message").classList,closeUpdatedMessage:document.getElementById("consent-close-updated-message"),consentBanner:document.getElementById("cookie-consent-banner")},Altis.Consent.has=function(e){return wp_has_consent(e)},Altis.Consent.set=function(e,t){wp_set_consent(e,t)},Altis.Consent.setCookie=function(e,t){consent_api_set_cookie(e,t)},Altis.Consent.getCookie=function(e){return consent_api_get_cookie(e)},Altis.Consent.cookieSaved=function(){let e=!1;return altisConsent.categories.filter(e=>"functional"!==e).forEach(t=>{Altis.Consent.has(t)&&(e=!0)}),e},Altis.Consent.getCategories=function(){let e=altisConsent.alwaysAllowCategories;return altisConsent.categories.forEach(t=>{e(t)&&e.push(t)}),e},Altis.Consent.updateCategories=function(){const e=document.getElementsByClassName("category-input");let t=[],s=[];if(Altis.Consent.cookiePreferences&&Altis.Consent.cookiePreferences.contains("show"))for(const n of e)n.checked?t.push(n.value):s.push(n.value);if("give-consent"===this.className){for(const e of altisConsent.categories)t.push(e);s=[]}else if("revoke-consent"===this.className)for(const e of altisConsent.categories)altisConsent.alwaysAllowCategories.includes(e)||s.push(e);t.push(...altisConsent.alwaysAllowCategories),Array.from(new Set(t)),Array.from(new Set(s));for(const e of t)Altis.Consent.set(e,"allow");for(const e of s)Altis.Consent.set(e,"deny");Altis.Consent.cookiePreferences&&Altis.Consent.cookiePreferences.contains("show")&&(Altis.Consent.cookiePreferences.remove("show"),Altis.Consent.giveConsentButton.classList.remove("hide"),Altis.Consent.revokeConsentButton.classList.remove("hide")),document.querySelector(".consent-banner").classList.add("hide"),Altis.Consent.preferencesUpdatedMessage()},Altis.Consent.toggleCookiePrefs=function(){const e=Altis.Consent.giveConsentButton.classList,t=Altis.Consent.revokeConsentButton.classList;Altis.Consent.cookiePreferences.toggle("show"),e.toggle("hide"),t.toggle("hide")},Altis.Consent.maybeDisplayBanner=function(){!Altis.Consent.cookieSaved()&&altisConsent.shouldDisplayBanner&&Altis.Consent.consentBanner&&(Altis.Consent.consentBanner.style.display="block")},Altis.Consent.preferencesUpdatedMessage=function(){document.querySelector(".consent-updated-message").classList.toggle("show")},Altis.Consent.maybeDisplayBanner(),Altis.Consent.giveConsentButton.addEventListener("click",Altis.Consent.updateCategories),Altis.Consent.revokeConsentButton.addEventListener("click",Altis.Consent.updateCategories),Altis.Consent.cookiePrefsButton&&(Altis.Consent.cookiePrefsButton.addEventListener("click",Altis.Consent.toggleCookiePrefs),Altis.Consent.applyCookiePrefs.addEventListener("click",Altis.Consent.updateCategories)),Altis.Consent.closeUpdatedMessage.addEventListener("click",()=>Altis.Consent.cookieUpdatedMessage.toggle("show"));