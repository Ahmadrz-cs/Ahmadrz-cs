import http from "k6/http";
import { check, group, sleep } from "k6";
import { Trend } from "k6/metrics";
import {parseHTML} from "k6/html";

export let options = {
  // average over n iterations
  iterations: 4, 
  vus: 1
};

/** Timing v */
let rtSignUp = new Trend("rt_ob1_signup", true);
let rtEmail = new Trend("rt_ob2_emailverify", true);
let rtPrefs = new Trend("rt_ob3_marketingpref", true);
let rtQuiz = new Trend("rt_ob4_quiz", true);
let rtCompliance = new Trend("rt_ob5_compliance", true);

let rtObtotal = new Trend("rt_ob0_total", true);

/** Vars for testing and cleanup */
const sfcredentials = {
  "id": __ENV["SALESFORCE_CONSUMER_KEY"] || "",
  "scrt": __ENV["SALESFORCE_CONSUMER_SECRET"] || "",
  "rtok": __ENV["SALESFORCE_REFRESH_TOKEN"] || "",
};
const file1 = open("../_data/org_img.jpg", "b");
// const mailcatcherurl = "localhost:1080";
// const baseurl = "http://local.qac.com";
// const backurl = "http://local.qa.com";
const mailcatcherurl = "mailcatcher.yielderverse.co.uk";
const baseurl = "https://test-front.yielderverse.co.uk";
const backurl = "https://test-back.yielderverse.co.uk";


/** Helper functions */
function genEmail() {
  return "kaysixseth" + String(Date.now()) + String(__VU -1)  + String(__ITER) + "@crowdtek.co.uk"
}

function getVerificationLink() {
  let res = http.get("http://admin:London123!@" + mailcatcherurl + "/messages/1.html");
  const doc = parseHTML(res.body);
  return doc.find('a:contains("Verify Email")').attr('href');
}

function clearMailcatcher() {
  let res = http.del("http://admin:London123!@" + mailcatcherurl + "/messages");
  check(res, {
    "status is 204": (r) => r.status === 204,
  });
}

function getSfToken() {
  let rsp = http.get(`https://login.salesforce.com/services/oauth2/token?format=json&grant_type=refresh_token&refresh_token=${sfcredentials["rtok"]}&client_id=${sfcredentials["id"]}&client_secret=${sfcredentials["scrt"]}`);
  check(rsp, {
    "status is 200": (r) => r.status === 200,
  });
  return rsp.json();
}

function getSfId(email) {
  let formdata = {
    "_username": email,
    "_password": "London123",
  };
  let rsp = http.post(backurl + "/api/login_check", formdata);
  rsp = http.get(backurl + "/v1/yielders/self", { headers: { "Authorization": "Bearer " + rsp.json()["data"]["token"] }})
  let userinfo = rsp.json()["data"]["user"]["info"];
  for (var i=0; i < userinfo.length; i++) {
    if (userinfo[i].type === "salesforce_id") {
      return userinfo[i].value;
    }
  }
}


/** Actual test code */
export function setup() {
  return {
    sfAuthInfo: getSfToken()
  };
}

export default function(data) {
  let totalrt = 0;
  let emailgen = genEmail(); 
  clearMailcatcher(); // this is NOT threadsafe behaviour, will encounter race conditions with concurrent VUs

  // Sign-up
  let rsp = http.get(baseurl + "/onboarding/sign-up");
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "signUpUser[firstname]": "Kay", 
      "signUpUser[lastname]": "Sixseth", 
      "signUpUser[email]": emailgen, 
      "signUpUser[password][first]": "London123",
      "signUpUser[password][second]": "London123"
    }, 
    submitSelector: "btn_continue" 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "signUpUser[term_service_accepted]": true
    }, 
    submitSelector: "btn_continue" 
  });
  rtSignUp.add(rsp.timings.duration);
  totalrt += rsp.timings.duration;

  // Email verification
  rsp = http.get(getVerificationLink());
  rtEmail.add(rsp.timings.duration);
  totalrt += rsp.timings.duration;

  // Regulation and marketing
  rsp = rsp.submitForm({ 
    fields: { 
      "userPreference[contact_via_email]": true
    }, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "userPreference[investor_type]": "cxb_restricted_investor"
    }, 
    submitSelector: '[type="submit"]' 
  });
  rtPrefs.add(rsp.timings.duration);
  totalrt += rsp.timings.duration;

  // Questionnaire
  rsp = rsp.submitForm({ 
    fields: {}, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "userKnowledge[question1]": "Invest manageable amounts in diverse range of properties"
    }, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "userKnowledge[question2]": "Yes"
    }, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "userKnowledge[question3]": "Less than £1k"
    }, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "userKnowledge[question4]": "I do not have a financial background, and Yielders will be my first investment"
    }, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: { 
      "userKnowledge[question5]": "The Yielders platform allows for me to sell my shares on the secondary market in the middle of an investment term. My money will only be repaid if someone buys my shares on the platform."
    }, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rtQuiz.add(rsp.timings.duration);

  // Compliance
  rsp = rsp.submitForm({ 
    fields: {}, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rsp = rsp.submitForm({ 
    fields: {
      "userInformation[honorific_prefix]": "Ms",
      "userInformation[gender]": "FEMALE",
      "userInformation[firstname]": "Kay",
      "userInformation[lastname]": "Sixseth",
      "userInformation[birthDate][day]": 8,
      "userInformation[birthDate][month]": 3,
      "userInformation[birthDate][year]": 1990,
      "userInformation[nationality]": "United Kingdom",
      "userInformation[address][country]": "United Kingdom",
      "userInformation[address][address1]": "2 Merry Lane",
      "userInformation[address][city]": "London",
      "userInformation[address][postal_code]": "SW12 7PQ",
      "userInformation[phone1]": "02072054650",
      "userInformation[phone2]": "07911123456",
      "userInformation[document][proof_of_id]": http.file(file1, "org_img.jpg"),
      "userInformation[document][proof_of_address]": http.file(file1, "org_img.jpg")
    }, 
    submitSelector: '[type="submit"]' 
  });
  totalrt += rsp.timings.duration;
  rtCompliance.add(rsp.timings.duration);
  rtObtotal.add(totalrt);

  // let doc = parseHTML(rsp.body);
  // const title = doc.find('h3').text(); 
  // console.log(title);

  // cleanup salesforce
  let sfid = getSfId(emailgen);
  rsp = http.del(`${data.sfAuthInfo["instance_url"]}/services/data/v45.0/sobjects/Contact/${sfid}`, {}, { headers: { "Authorization": "Bearer " + data.sfAuthInfo["access_token"] }});
  check(rsp, {
    "status is 204": (r) => r.status === 204,
  });
};