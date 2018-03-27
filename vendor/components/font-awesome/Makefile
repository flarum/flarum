REF = 

default: data
	@mkdir -p css fonts scss less
	@cd $< && git remote update && git checkout master && ( git branch -D work || true ) && git checkout -b work $(REF)
	@cp -f $</css/* ./css
	@cp -f $</fonts/* ./fonts
	@cp -f $</scss/* ./scss
	@cp -f $</less/* ./less
	@du -bh css* font* scss* less*

data:
	@git clone https://github.com/FortAwesome/Font-Awesome.git $@

.PHONY: default
