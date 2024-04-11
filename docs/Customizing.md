# Customizing your prototype
Suppose you have generated a prototype from your Ampersand script.
Now, what if you want to change something in your prototype that cannot be done by changing things in Ampersand. This can range from simple things like adding a video link in your landing page to more complicated things like changing functionality in your front-end application. You don't want to edit the code after generating because you would be doing that each time you generate in the further development of your application.

We approach this problem by letting Docker do the post-editing for you. You put the files you want to change in a customizations folder.

To solve this problem, you need to understand how docker builds your application. Consult the [Dockerfile](./project/Dockerfile) to see the actual steps it takes. These are the steps:
1. Docker copies the source code of your application into an empty directory in your container. This will be your working directory.
2. The Ampersand compiler builds a prototype in the directory `/var/www/` in your container.
3. The Docker copies the contents of your customization folder to the generated application, to ensure that this code overrules anything that the Ampersand compiler has built.
4. Then, docker builds the web-application. Note that the last RUN-statement in the Dockerfile, marked with the comment `# uncomment ...`, is only necessary if there are customizations. Without cusomizations, this RUN-statement is superfluous and only consumes valuable build-time.

This way, docker "post-edits" your changes into the generated software. Of course, you need to know what you are doing. The best way is to study the [prototype framework](https://github.com/AmpersandTarski/Prototype) to discover which code you want to substitute.