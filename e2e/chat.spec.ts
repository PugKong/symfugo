import { Browser } from "@playwright/test";
import { test } from "./test";
import { ChatPage } from "./chat";

async function makeChatPage(browser: Browser, username: string): Promise<ChatPage> {
  const context = await browser.newContext({
    httpCredentials: { username: username, password: "any" },
  });
  const page = await context.newPage();

  return new ChatPage(page);
}

test("users can chat", async ({ browser }) => {
  const johnUsername = "John";
  const janeUsername = "Jane";

  const johnChatPage = await makeChatPage(browser, johnUsername);
  const janeChatPage = await makeChatPage(browser, janeUsername);

  await johnChatPage.visit();
  await janeChatPage.visit();

  const messages: { text: string; username: string }[] = [];
  await johnChatPage.hasMessages(messages);
  await janeChatPage.hasMessages(messages);

  let message = { text: "Hello!", username: johnUsername };
  await johnChatPage.send(message.text);
  messages.push(message);
  await johnChatPage.hasMessages(messages);
  await janeChatPage.hasMessages(messages);

  message = { text: "Hey!", username: janeUsername };
  await janeChatPage.send(message.text);
  messages.push(message);
  await johnChatPage.hasMessages(messages);
  await janeChatPage.hasMessages(messages);
});
