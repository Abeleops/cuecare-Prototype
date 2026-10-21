document.addEventListener("DOMContentLoaded", () => {
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
  document
    .querySelectorAll(".recommendation input")
    .forEach((input) =>
      input.addEventListener("change", () =>
        input
          .closest(".recommendation")
          ?.classList.toggle("done", input.checked),
      ),
    );
});
