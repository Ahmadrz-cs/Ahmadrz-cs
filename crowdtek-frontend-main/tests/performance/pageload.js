import { check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";

// const baseurl = "https://test-front.yielderverse.co.uk";
const baseurl = "http://front.dev.local";
// const baseurl = "http://local.qac.com";
let user_to_login = "ben.auto@test.yielderverse.co.uk";
// let user_to_login = "superadmin@test.yielderverse.co.uk";

let home_rt = new Trend("rt_home_page", true);
let property_rt = new Trend("rt_property_page", true);
let hiw_rt = new Trend("rt_howitworks_page", true);
let about_rt = new Trend("rt_about_page", true);

let login_rt = new Trend("rt_login_page", true);
let login_action = new Trend("rt_login_post", true);
let portfolio_rt = new Trend("rt_portfolio_page", true);
let profile_rt = new Trend("rt_profile_page", true);
let signup_rt = new Trend("rt_signup_page", true);

// for our benchmarking
export let options = {
  // average over n iterations
  iterations: 8,
  vus: 1
};

export default function () {
  group("Visiting user browsing", function () {
    group("Homepage", function () {
      let res = http.get(baseurl + "/");
      check(res, {
        "status is 200": (r) => r.status === 200,
      });
      home_rt.add(res.timings.duration);
    });
    group("Properties intro", function () {
      let res = http.get(baseurl + "/properties-introduction");
      property_rt.add(res.timings.duration);
    });
    group("How it works", function () {
      let res = http.get(baseurl + "/process/how-it-works");
      hiw_rt.add(res.timings.duration);
    });
    group("About us", function () {
      let res = http.get(baseurl + "/about-us");
      about_rt.add(res.timings.duration);
    });
  });

  group("User Engagement starting pages", function () {
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
        console.log('Login post response url ' + String(res.url));
        // console.log('Login post response time was ' + String(res.timings.duration) + ' ms');
        check(res, {
          "status is 200": (r) => r.status === 200,
          'verify homepage text': (r) =>
            r.body.includes("Don't invest unless"),
        });
        login_action.add(res.timings.duration);
        // const doc = parseHTML(res.body);  // convert to html
        // const hasportfolio = doc.find('header').attr('class'); //content navbar-fixed-top   logged-in   
        // console.log(hasportfolio);
        res = http.get(baseurl + "/my-portfolio");
        portfolio_rt.add(res.timings.duration);

        res = http.get(baseurl + "/my-profile");
        profile_rt.add(res.timings.duration);
      });
    });

    group("Signup", function () {
      let rsp = http.get(baseurl + "/sign-up");
      signup_rt.add(rsp.timings.duration);
    });
  });
};