import { Centrifuge } from "centrifuge";

const chatContainer = document.getElementById("chat");
const messagesContainer = document.getElementById("messages");

const websocketUrl = chatContainer.dataset.websocketUrl;
const token = chatContainer.dataset.token;

const centrifuge = new Centrifuge(websocketUrl, {
  token: token,
});

centrifuge
  .on("connecting", function (ctx) {
    console.log(`connecting: ${ctx.code}, ${ctx.reason}`);
  })
  .on("connected", function (ctx) {
    console.log(`connected over ${ctx.transport}`);
  })
  .on("disconnected", function (ctx) {
    console.log(`disconnected: ${ctx.code}, ${ctx.reason}`);
  })
  .on("publication", function (ctx) {
    const template = document.createElement("template");

    template.innerHTML = ctx.data;

    messagesContainer.appendChild(template.content.children[0]);
  })
  .connect();
