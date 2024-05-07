import { expect, Page, Locator } from "@playwright/test";

export class ChatPage {
  readonly page: Page;
  readonly url: string = "/chat";

  readonly messagesLocator: Locator;

  readonly messageInputLocator: Locator;
  readonly messageSendLocator: Locator;

  constructor(page: Page) {
    this.page = page;

    this.messagesLocator = page.locator("#messages > div");

    this.messageInputLocator = page.getByLabel("Message", { exact: true });
    this.messageSendLocator = page.getByRole("button", { name: "Send", exact: true });
  }

  async visit() {
    await this.page.goto(this.url);

    await expect(this.messageInputLocator).toBeVisible();
    await expect(this.messageSendLocator).toBeVisible();
  }

  async hasMessages(messages: { text: string; username: string }[]) {
    await expect(this.messagesLocator).toHaveCount(messages.length);

    const messageLocators = await this.messagesLocator.all();
    for (let i = 0; i < messages.length; i++) {
      const message = messages[i];
      const messageLocator = messageLocators[i].locator("div");

      await expect(messageLocator).toHaveText([message.text, "by " + message.username]);
    }
  }

  async send(text: string) {
    await this.messageInputLocator.fill(text);
    await this.messageSendLocator.click();
  }
}
