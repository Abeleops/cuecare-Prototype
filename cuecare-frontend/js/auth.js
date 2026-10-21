document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".brand-mark").forEach((mark) => {
    const image = document.createElement("img");
    image.className = "brand-image";
    image.alt = "CueCare logo";
    image.src = new URL(
      "../assets/logo.png",
      document.querySelector('link[href*="css/style.css"]')?.href ||
        window.location.href,
    ).href;
    mark.replaceWith(image);
  });
  document.querySelectorAll("[data-toggle-password]").forEach((button) =>
    button.addEventListener("click", () => {
      const input = button.parentElement.querySelector("input");
      const showing = input.type === "text";
      input.type = showing ? "password" : "text";
      button.textContent = showing ? "Show" : "Hide";
    }),
  );
  const form = document.querySelector("#loginForm");
  if (form)
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      window.location.href = "dashboard.html";
    });
  const current = document.body.dataset.page;
  document.querySelectorAll("[data-nav]").forEach((link) => {
    if (link.dataset.nav === current) link.classList.add("active");
  });
  document
    .querySelectorAll(".mobile-menu")
    .forEach((button) =>
      button.addEventListener("click", () =>
        document.querySelector(".sidebar")?.classList.toggle("open"),
      ),
    );
  document
    .querySelectorAll(".sidebar-toggle")
    .forEach((button) =>
      button.addEventListener("click", () =>
        document.querySelector(".sidebar")?.classList.remove("open"),
      ),
    );
});
