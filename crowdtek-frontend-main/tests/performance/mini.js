import { check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";

// const baseurl = "https://dev-front.yielderverse.co.uk";
const baseurl = "http://front.dev.local";

let home_rt = new Trend("rt_home_page", true);

// for our benchmarking
export let options = {
  // average over n iterations
  // iterations: 8, 
  duration: '30s',
  vus: 1
};

export default function() {
  group("Visiting user browsing", function() {
    group("Homepage", function() {
      let res = http.get(baseurl + "/");
      check(res, {
        "status is 200": (r) => r.status === 200,
      });
      home_rt.add(res.timings.duration);
    });
  });
};
