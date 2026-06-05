fetch("https://ts7.x1.arabics.travian.com/api/v1/troop/send", {
  "headers": {
    "accept": "application/json, text/javascript, */*; q=0.01",
    "accept-language": "en,ar;q=0.9",
    "cache-control": "no-cache",
    "content-type": "application/json; charset=UTF-8",
    "pragma": "no-cache",
    "priority": "u=1, i",
    "sec-ch-ua": "\"Chromium\";v=\"148\", \"Google Chrome\";v=\"148\", \"Not/A)Brand\";v=\"99\"",
    "sec-ch-ua-mobile": "?0",
    "sec-ch-ua-platform": "\"Windows\"",
    "sec-fetch-dest": "empty",
    "sec-fetch-mode": "cors",
    "sec-fetch-site": "same-origin",
    "x-nonce": "FQxEVnE8yWgJqoPZQ5piwjtDLTpqiojlB0Q09TTdOq5VwSwtl17S03kBckja2V27",
    "x-requested-with": "XMLHttpRequest"
  },
  "referrer": "https://ts7.x1.arabics.travian.com/hero/adventures",
  "body": "{\"action\":\"troopsSend\",\"eventType\":50,\"troops\":[{\"t11\":1}],\"target\":{\"adventureId\":55}}",
  "method": "POST",
  "mode": "cors",
  "credentials": "include"
});