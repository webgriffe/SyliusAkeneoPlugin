# UPGRADE FROM `v1.13.3` TO `v2.0.0`

## Codebase

### Use PHP 8 syntax (#128)

#### Changed
- [BC] The return type of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to a non-contravariant array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverterInterface#convert() changed from no type to array|bool|int|string
- [BC] The parameter $value of Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter#convert() changed from no type to a non-contravariant array|bool|int|string
