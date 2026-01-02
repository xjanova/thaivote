import { describe, it, expect } from "vitest";

describe("App", () => {
  it("should pass basic test", () => {
    expect(true).toBe(true);
  });

  it("should have math working correctly", () => {
    expect(1 + 1).toBe(2);
  });
});
