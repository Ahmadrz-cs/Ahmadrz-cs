# Contents of /Porting service subdirectory

- Contains services exclusively used for switching over to the TradeOrder and ShareTrade based trading system
- Services will be involve in porting over existing data to work with the new trading system
- Composed of several steps
  - Mapper service(s) - that will take an existing entity and create the appropriate new entities related to it
  - Porting manager(s) - coordinates repository and mapper services to actually execute the porting process