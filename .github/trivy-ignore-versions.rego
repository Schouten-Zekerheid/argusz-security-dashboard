package trivy

default ignore = false

ignore {
    input.FixedVersion != ""
    installed := to_number(split(input.InstalledVersion, ".")[0])
    fixed := to_number(split(input.FixedVersion, ".")[0])
    fixed < installed
}