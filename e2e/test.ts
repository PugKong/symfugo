import { test as base } from "@playwright/test";
import { GenericContainer, Network, StartedNetwork, StartedTestContainer } from "testcontainers";

export const test = base.extend({
  baseURL: async ({}, use) => {
    if (process.env.E2E_USE_CONTAINERS) {
      const containers = new Containers();
      const baseURL = await containers.start();
      await use(baseURL);
      await containers.stop();
    } else {
      await use("http://symfugo.localhost");
    }
  },
});

class Containers {
  private network: StartedNetwork;
  private caddy: StartedTestContainer;
  private centrifugo: StartedTestContainer;
  private php: StartedTestContainer;

  async start() {
    this.network = await new Network().start();

    this.caddy = await new GenericContainer("symfugo-caddy-e2e").withNetwork(this.network).withExposedPorts(80).start();

    const port = this.caddy.getMappedPort(80);
    const baseURL = `http://symfugo.localhost:${port}`;

    const centrifugoKeys = {
      CENTRIFUGO_TOKEN_HMAC_SECRET_KEY: "token secret key",
      CENTRIFUGO_API_KEY: "api key",
    };

    const centrifugoAlias = "centrifugo";
    this.centrifugo = await new GenericContainer("centrifugo/centrifugo:v5")
      .withNetwork(this.network)
      .withNetworkAliases(centrifugoAlias)
      .withEnvironment({
        ...centrifugoKeys,
        CENTRIFUGO_ALLOWED_ORIGINS: baseURL,
      })
      .start();

    this.php = await new GenericContainer("symfugo-php-e2e")
      .withNetwork(this.network)
      .withNetworkAliases("php")
      .withEnvironment({
        ...centrifugoKeys,
        CENTRIFUGO_BASE_URL: `http://${centrifugoAlias}:8000`,
        CENTRIFUGO_WEBSOCKET_URL: `ws://cent.localhost:${port}/connection/websocket`,
      })
      .withBindMounts([
        {
          source: process.cwd() + "/var/coverage",
          target: "/app/var/coverage",
        },
      ])
      .start();

    return baseURL;
  }

  async stop() {
    await this.caddy.stop();
    await this.centrifugo.stop();
    await this.php.stop();
    await this.network.stop();
  }
}
