module.exports = {
  ci: {
    collect: {
      startServerCommand: "symfony server:start --port=8000 --no-tls",
      startServerReadyPattern: "started|listening|8000",
      startServerReadyTimeout: 20000,
      url: ["http://127.0.0.1:8000/"],
      numberOfRuns: 1,
      settings: {
        chromeFlags: "--no-sandbox --disable-setuid-sandbox",
      },
    },
    assert: {
      assertions: {
        "categories:accessibility": ["warn", { minScore: 0.8 }],
        "categories:performance": ["warn", { minScore: 0.5 }],
      },
    },
  },
};
