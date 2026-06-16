import hljs from "highlight.js/lib/core";
import php from "highlight.js/lib/languages/php";
import javascript from "highlight.js/lib/languages/javascript";
import typescript from "highlight.js/lib/languages/typescript";
import python from "highlight.js/lib/languages/python";
import go from "highlight.js/lib/languages/go";
import java from "highlight.js/lib/languages/java";
import ruby from "highlight.js/lib/languages/ruby";
import yaml from "highlight.js/lib/languages/yaml";
import json from "highlight.js/lib/languages/json";
import bash from "highlight.js/lib/languages/bash";
import xml from "highlight.js/lib/languages/xml";
import dockerfile from "highlight.js/lib/languages/dockerfile";
import vue from "highlight.js/lib/languages/xml"; // Vue SFC = XML/HTML superset

hljs.registerLanguage("php", php);
hljs.registerLanguage("javascript", javascript);
hljs.registerLanguage("typescript", typescript);
hljs.registerLanguage("python", python);
hljs.registerLanguage("go", go);
hljs.registerLanguage("java", java);
hljs.registerLanguage("ruby", ruby);
hljs.registerLanguage("yaml", yaml);
hljs.registerLanguage("json", json);
hljs.registerLanguage("bash", bash);
hljs.registerLanguage("xml", xml);
hljs.registerLanguage("vue", xml); // Vue SFC highlighted as XML/HTML
hljs.registerLanguage("dockerfile", dockerfile);
hljs.registerLanguage("hcl", bash);

window.hljs = hljs;

document.addEventListener("alpine:init", () => {
    Alpine.magic("nlDate", () => (iso, withTime = false) => {
        if (!iso || iso === "—") return iso || "—";
        try {
            const opts = withTime
                ? {
                      day: "numeric",
                      month: "long",
                      year: "numeric",
                      hour: "2-digit",
                      minute: "2-digit",
                  }
                : { day: "numeric", month: "long", year: "numeric" };
            return new Intl.DateTimeFormat("nl-NL", opts).format(new Date(iso));
        } catch {
            return iso;
        }
    });
});
