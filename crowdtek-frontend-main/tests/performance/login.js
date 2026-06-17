import { check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";

// const baseurl = "https://dev-front.yielderverse.co.uk";
const baseurl = "http://front.dev.local";

let user_to_login = "ben.auto@test.yielderverse.co.uk";

let login_rt = new Trend("rt_login_page", true);
let login_action = new Trend("rt_login_post", true);
let properties_rt = new Trend("rt_properties_page", true);
let portfolio_rt = new Trend("rt_portfolio_page", true);
// let profile_rt = new Trend("rt_profile_page", true);
// let signup_rt = new Trend("rt_signup_page", true);

export let options = {
  duration: '30s',
  vus: 1
};

export default function () {
  group("Authenticated User", function () {
    group("Login", function () {
      group("Login Page", function () {
        let rsp = http.get(baseurl + "/login");
        login_rt.add(rsp.timings.duration);
      });
      group("Login Action", function () {
        // User without 2FA step
        // let formdata = {
        //   "sign_in_type[email]": "superadmin@test.yielderverse.co.uk",
        //   "sign_in_type[password]": "HarvestBounty!756",
        // };
        // let headers = { "Content-Type": "application/x-www-form-urlencoded" };
        // let res = http.post(baseurl + "/login", formdata, { headers: headers });
        let res = http.get(baseurl + "/login");
        res = res.submitForm({
          formSelector: 'form',
          fields: { _username: user_to_login, _password: "HarvestBounty!756" },
        });
        // console.log('Login post response status was ' + String(res.status));
        // console.log('Login post response url ' + String(res.url));
        // console.log('Login post response time was ' + String(res.timings.duration) + ' ms');
        check(res, {
          "status is 200": (r) => r.status === 200,
          'verify homepage text': (r) =>
            r.body.includes("Don't invest unless"),
        });
        login_action.add(res.timings.duration);
        
        res = http.get(baseurl + "/current-properties");
        check(res, {
          "status is 200": (r) => r.status === 200,
          'verify properties text': (r) =>
            r.body.includes("potential high yielding assets"),
        });
        properties_rt.add(res.timings.duration);

        res = http.get(baseurl + "/my-portfolio");
        check(res, {
          "status is 200": (r) => r.status === 200,
          'verify portfolio text': (r) =>
            r.body.includes("Total Currently Invested"),
        });
        portfolio_rt.add(res.timings.duration);

        // res = http.get(baseurl + "/my-profile");
        // profile_rt.add(res.timings.duration);
      });
    });
  });
};
